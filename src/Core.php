<?php
namespace Tribe;

use \Tribe\MySQL;
use \Tribe\Config;

class Core {
	public static $ignored_keys;

	public function __construct()
	{
		self::$ignored_keys = ['type', 'function', 'class', 'slug', 'id', 'updated_on', 'created_on', 'user_id', 'files_descriptor', 'password_md5', 'role_slug', 'mysql_access_log', 'mysql_activity_log'];

        if ('dev' == strtolower($_ENV['ENV'])) {
            error_reporting(E_ALL);
            ini_set('display_errors', 1);
            ini_set('display_startup_errors', 1);
        } else {
            ini_set('display_errors', 0);
            ini_set('display_startup_errors', 0);
        }
	}

	public function executeShellCommand($cmd)
	{
		ob_start();
		passthru($cmd);
		$tml = ob_get_contents();
		ob_end_clean();
		return $tml;
	}

	public function slugify($string, $input_itself_is_unique = 0)
	{
		//size of slug should be less than 255 characters because of DB field, so 230 + length of uniqid()
		$slug = substr(strtolower(trim(preg_replace('/[^A-Za-z0-9_-]+/', '-', ($string ? $string : 'untitled')))), 0, 230) . ($input_itself_is_unique ? '' : '-' . uniqid());
		return $slug;
	}

	public function unslugify($url_part)
	{
        if (strstr($url_part, '?') != NULL)
            $url_part = explode('?', $url_part)[0];
        return strtolower(trim(rawurlencode($url_part)));
	}

	public function pushObject(array $post, bool $overwrite_post = false)
	{
		$sql = new MySQL();
		$config = new Config();

		$types = $config->getTypes();
		$updated_on = time();
		$posttype = $post['type'];

		$is_new_record = !isset($post['id']);

		//Get title/primary module structure to understand if uniqueness of title has to be checked and to get what's the title called in that type.
		if ($posttype) {
			$title_module = $config->getTypePrimaryModule($posttype, $types);
			$title_slug = $title_module['slug'];
			$title_unique = $title_module['unique'];
		}

		//ID Type correction
		if ($post['id'] ?? false) {
			$post['id'] = (int) $post['id'];
		}

		//Checking / using variable type if it is given with modules
		foreach ($types[$posttype]['modules'] as $module) {

			//change var_type if available, before saving to database
			if ($module['var_type'] ?? false) {
				$slug = $module['input_slug'];
				if ($module['var_type'] == 'int') {
					$post[$slug] = (int) $post[$slug];
				} else if ($module['var_type'] == 'float') {
					$post[$slug] = (float) $post[$slug];
				} else if ($module['var_type'] == 'bool') {
					$post[$slug] = (bool) ($post[$slug] ?? false);
				}

			}

		}

		//Title uniqueness if title_unique is set, function stops and returns 0 if a conflict is found
		if ($title_unique ?? false) {
			$q = $sql->executeSQL("SELECT `id` FROM `data` WHERE `type`='" . $post['type'] . "' && `content`->'$." . $title_slug . "'='" . mysqli_real_escape_string($sql->databaseLink, $post[$title_slug]) . "' ORDER BY `id` DESC LIMIT 0,1");

			if (is_array($q) && $q[0]['id'] && $post['id'] != $q[0]['id']) {
				return 0;
			}
		}

		//Setting a slug if required (when new post or when slug update is demanded)
		if (!trim($post['slug'] ?? '') || ($post['slug_update'] ?? '')) {
			$_title_slug = isset($title_slug) ? ($post[$title_slug] ?? '') : '';
			$_title_uniqie = $title_unique ?? '';

			$post['slug'] = $this->slugify($_title_slug, $_title_uniqie);
			unset($post['slug_update']);
		}

		
		if (!($post['id'] ?? null)) {
			//If it's a new post, first generate an ID
			$sql->executeSQL("INSERT INTO `data` (`created_on`) VALUES ('$updated_on')");
			$post['id'] = $sql->lastInsertID();
			$is_new_record = true;
		}
		else if (!$overwrite_post) {
			//Avoiding over-writing of entire array: If it is not a new record, then having the array already stored makes sure only fields provided are overwritten, not the whole array.
			$post = array_merge($this->getObject($post['id']), $post);
		}

		$sql->executeSQL("UPDATE `data` SET `content`='" . mysqli_real_escape_string($sql->databaseLink, json_encode($post)) . "', `updated_on`='$updated_on' WHERE `id`='" . $post['id'] . "'");
		$id = (int) $post['id'];

		return $id;
	}

	public function pushAttribute($id, $meta_key, $meta_value = ''): bool
	{
		$sql = new MySQL();

		if (!($id && $meta_key)) {
			return 0;
		}

		if (!trim($meta_value)) { // to delete a key, when left empty
			$q = $sql->executeSQL("UPDATE data SET content = JSON_REMOVE(content, '$.$meta_key') WHERE id='$id'");
		} else {
			$meta_value = $sql->databaseLink->real_escape_string($meta_value);
			$q = $sql->executeSQL("UPDATE data SET content = JSON_SET(content, '$.$meta_key', '$meta_value') WHERE id='$id'");
		}

		return 1;
	}

	public function getAttribute($identifier, $meta_key)
	{
		$attr = $this->getAttributes($identifier, $meta_key);
		return $attr[$meta_key];
	}

	public function getAttributes($identifier, $meta_keys)
	{
		$sql = new MySQL();

		$meta_keys = explode(',', $meta_keys);

		foreach ($meta_keys as $meta_key) {
			$meta_key = trim($meta_key);

			if ( $meta_key != 'content' && in_array($meta_key, $sql->schema) ) {
				$qry[] = "`" . $meta_key . "`";
			} else {
				$qry[] = "`content`->>'$." . $meta_key . "' AS `" . $meta_key . "`";
			}
		}

		$qry = implode(', ', $qry);

		if (is_numeric($identifier)) {
			$q = $sql->executeSQL("SELECT " . $qry . " FROM `data` WHERE `id`='$identifier' LIMIT 0,1");
		} else {
			$q = $sql->executeSQL("SELECT $qry FROM data WHERE slug='{$identifier['slug']}' && type='{$identifier['type']}' LIMIT 0,1");
		}

		return $q[0];
	}

	public function getObject($identifier, $object_structure=array())
	{
		$sql = new MySQL();

		//IF KEY IS NUMBERIC, IT MEANS SINGLE ID
		if (is_numeric($identifier)) {
			$q = $sql->executeSQL("SELECT * from data
                where id = '{$identifier}'
                order by id desc
                limit 0,1
            ");
		}

		//IF ARRAY HAS SINGLE type and slug
		else if ($identifier['type'] && $identifier['slug']) {
			$q = $sql->executeSQL("SELECT * from data
                where
                    slug = '{$identifier['slug']}' and
                    type = '{$identifier['type']}'
                order by id desc
                limit 0,1
            ");
		}

		else {
			return 0;
		}

        return $this->contentCleanup($q, $object_structure, 0);
	}

	public function getObjects($identifier, $object_structure=array())
	{
		$sql = new MySQL();

		//IF CSV, COMMA SEPARATED IDS
		if (is_string($identifier)) {
			$q = $sql->executeSQL("SELECT * from data
                where id IN (".$identifier.")
                order by id desc
                limit 0,".count(explode(',', $identifier))
            );
		}

		//IF KEY IS ARRAY AS IN get_ids
		else if ($identifier[0]['id']) {
			$q = $sql->executeSQL("SELECT * from data
                where id IN (".implode( ",", array_column($identifier, 'id') ).")
                order by id desc
                limit 0,".count($identifier)
            );
		}

		//IF ARRAY HAS type and slugs
		else if ($identifier['type'] && $identifier['slugs'][0]) {
			foreach ($identifier['slugs'] as $idn) {
				$_where[] = "(`type`='".$identifier['type']."' AND `slug`='".$idn."')";
			}

			$q = $sql->executeSQL("SELECT * from data
                where ".implode(' OR ', $_where)."
                order by id desc
                limit 0,".count($identifier['slugs'])
            );
		}

		//IF ARRAY HAS multiple type-slug pairs
		else if ($identifier[0]['type'] && $identifier[0]['slug']) {
			foreach ($identifier as $idn) {
				$_where[] = "(`type`='".$idn['type']."' AND `slug`='".$idn['slug']."')";
			}

			$q = $sql->executeSQL("SELECT * from data
                where ".implode(' OR ', $_where)."
                order by id desc
                limit 0,".count($identifier)
            );
		}

		else {
			return 0;
		}

        return $this->contentCleanup($q, $object_structure);
	}

	public function getTypeObjectsCount($type) {
		$sql = new MySQL();
		$q = $sql->executeSQL("SELECT COUNT(`id`) AS `total` FROM `data` WHERE `type`='$type'");
		return (int) $q[0]['total'];
	}

	public function contentCleanup($rows, $object_structure=array(), $return_multi_array=1)
	{
		$config = new Config();
		$types = $config->getTypes();

        if (!($rows[0]['id'] ?? null)) {
            return 0;
        }

        foreach ($rows as $q ) {
        	$id = $q['id'];

			$final_response[$id] = json_decode($q['content'], true);
			$final_response[$id]['id'] = $q['id'];
			$final_response[$id]['updated_on'] = $q['updated_on'];
			$final_response[$id]['created_on'] = $q['created_on'];

			if (is_array($object_structure ?? false) && array_keys($object_structure)) {
				$final_response[$id] = array_intersect_key($final_response[$id], $object_structure);
			}

		}

		if ($return_multi_array && count($final_response))
			return $final_response;
		else if ($id)
			return $final_response[$id];
		else
			return false;
	}

	public function deleteObject(int $id): bool
	{
		$sql = new MySQL();
		$config = new Config();

		$types = $config->getTypes();

		$role_slug = $this->getAttribute($id, 'role_slug');
		$role_slug = $role_slug ? "&role=$role_slug" : '';

		if (!$id) {
			return false;
		}

		if ($types['webapp']['soft_delete_records']) {
			$sql->executeSQL("UPDATE data SET content = JSON_SET(content, '$.deleted_type', content->>'$.type', '$.type', 'deleted_record') WHERE id={$id}");
		} else {
			$q = $sql->executeSQL("DELETE FROM data WHERE id={$id}");
		}

		return true;
	}

	public function deleteObjects(array $ids, string $redirect_type): bool
	{
		$sql = new MySQL;
		$config = new Config();

		$types = $config->getTypes();
		$ids = implode(',', $ids);

		if ($types['webapp']['soft_delete_records']) {
			// soft delete
			$sql->executeSQL("UPDATE data SET content = JSON_SET(content, '$.deleted_type', content->>'$.type', '$.type', 'deleted_record') WHERE id IN ($ids)");
		} else {
			// perma delete
			$sql->executeSQL("DELETE FROM data WHERE id IN ($ids)");
		}

		return true;
	}

	public function getIDs(
		array $search_arr,
		string $limit = "0, 25",
		string|array $sort_field = 'id',
		string|array $sort_order = 'DESC',
		bool $show_public_objects_only = true,
		bool $show_partial_search_results = false,
		bool $show_case_sensitive_search_results = false,
		string|array $comparison_within_module_phrase = 'LIKE',
		string|array $inbetween_same_module_phrases = 'OR',
		string $between_different_module_phrases = 'AND',
		bool $debug_show_sql_statement = false)
	{
		$sql = new MySQL();
		if (is_array($sort_field)) {
			$k = 0;
			$priorities = [];
			foreach ($sort_field as $val) {
				if ($val != 'content' && in_array($val, $sql->schema) ) {
					$priorities[] = "`" . $val . "` " . $sort_order[$k];
				} else {
					$priorities[] = "`content`->>'$." . $val . "' " . $sort_order[$k];
				}
				$k++;
			}
			$priorities[] = "`id` DESC";
			$priority = implode(', ', $priorities);
		}
		else {
			if ($sort_field != 'content' && in_array($sort_field, $sql->schema) ) {
				$priority = "`" . $sort_field . "` " . $sort_order;
			} else {
				$priority = "`content`->>'$." . $sort_field . "' " . $sort_order . ", `id` DESC";
			}
		}

		$query_phrases = array();
		$i = 0;
		if (!is_array($comparison_within_module_phrase)) {
			$comparison_within_module_phrase_arr = array_fill(0, count($search_arr), $comparison_within_module_phrase);
		} else {
			$comparison_within_module_phrase_arr = $comparison_within_module_phrase;
		}

		if (!is_array($inbetween_same_module_phrases)) {
			$inbetween_same_module_phrases_arr = array_fill(0, count($search_arr), $inbetween_same_module_phrases);
		} else {
			$inbetween_same_module_phrases_arr = $inbetween_same_module_phrases;
		}

		foreach ($search_arr as $key => $value) {
			if (is_array($value)) {
				$query_phrases_temp = array();
				foreach ($value as $kv => $vv) {
					if ($show_case_sensitive_search_results)
						$query_phrases_temp[] = "`content`->>'$." . $kv . "' " . $comparison_within_module_phrase_arr[$i] . " " . (trim($vv) ? "'" . ($show_partial_search_results?"%":"") . $vv . ($show_partial_search_results?"%":"") . "'" : "");
					else
						$query_phrases_temp[] = "LOWER(`content`->>'$." . $kv . "') " . $comparison_within_module_phrase_arr[$i] . " " . (trim($vv) ? "'" . ($show_partial_search_results?"%":"") . strtolower($vv) . ($show_partial_search_results?"%":"") . "'" : "");
				}
				$query_phrases[] = join(' ' . $inbetween_same_module_phrases_arr[$i] . ' ', $query_phrases_temp);
			} else {
				if ($show_case_sensitive_search_results)
					$query_phrases[] = "`content`->>'$." . $key . "' " . $comparison_within_module_phrase_arr[$i] . " " . (trim($value) ? "'" . ($show_partial_search_results?"%":"") . $value . ($show_partial_search_results?"%":"") . "'" : "");
				else
					$query_phrases[] = "LOWER(`content`->>'$." . $key . "') " . $comparison_within_module_phrase_arr[$i] . " " . (trim($value) ? "'" . ($show_partial_search_results?"%":"") . strtolower($value) . ($show_partial_search_results?"%":"") . "'" : "");
			}

			$i++;
		}

		$qry = "SELECT `id` FROM `data` WHERE " . ($search_arr['type']!='user' ? ($show_public_objects_only ? "`content_privacy`='public' AND " : ""):"") . join(' ' . $between_different_module_phrases . ' ', $query_phrases) . " ORDER BY " . $priority . ($limit ? " LIMIT " . $limit : "");

		error_log($qry);
		
		$r = $sql->executeSQL($qry);

		if ($debug_show_sql_statement) {
			echo $qry;
		}

		return $r;
	}
}