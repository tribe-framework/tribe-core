<?php
namespace Tribe;

use \Tribe\MySQL;
use \Tribe\Config;
use \Tribe\Typesense;

class Core {
	public static $ignored_keys;
	private $typesense;
	private $searchEnabled;

	public function __construct()
	{
		self::$ignored_keys = ['type', 'function', 'class', 'slug', 'id', 'updated_on', 'created_on', 'user_id', 'files_descriptor', 'password_md5', 'role_slug', 'mysql_access_log', 'mysql_activity_log'];

        if ($_ENV['DISPLAY_ERRORS'] === true) {
            error_reporting(E_ALL);
            ini_set('display_errors', 1);
            ini_set('display_startup_errors', 1);
        } else {
            ini_set('display_errors', 0);
            ini_set('display_startup_errors', 0);
        }

        // Initialize Typesense
        $this->searchEnabled = ($_ENV['TYPESENSE_ENABLED'] ?? 'true') === 'true';
        if ($this->searchEnabled) {
            try {
                $this->typesense = new Typesense();
                if (!$this->typesense->isHealthy()) {
                    error_log("Typesense is not healthy, disabling search features");
                    $this->searchEnabled = false;
                }
            } catch (Exception $e) {
                error_log("Failed to initialize Typesense: " . $e->getMessage());
                $this->searchEnabled = false;
            }
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

		//Setting a slug if required (when new post or when slug update is demanded)
		if (!trim($post['slug'] ?? '') || ($post['slug_update'] ?? '')) {
			$_title_slug = isset($title_slug) ? ($post[$title_slug] ?? '') : '';
			$_title_uniqie = $title_unique ?? '';

			$post['slug'] = $this->slugify($_title_slug, $_title_uniqie);
			unset($post['slug_update']);
		}

		//Title uniqueness if title_unique is set, function stops and returns 0 if a conflict is found
		if ($title_unique ?? false) {
			$q = $sql->executeSQL("SELECT `id` FROM `data` WHERE `type`='" . $post['type'] . "' && `slug`='" . $post['slug'] . "' ORDER BY `id` DESC LIMIT 0,1");

			if (is_array($q) && $q[0]['id'] && $post['id'] != $q[0]['id']) {
				return 0;
			}
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

		// Add updated_on timestamp
		$post['updated_on'] = $updated_on;

		$sql->executeSQL("UPDATE `data` SET `content`='" . mysqli_real_escape_string($sql->databaseLink, json_encode(array_filter($post))) . "', `updated_on`='$updated_on' WHERE `id`='" . $post['id'] . "'");
		$id = (int) $post['id'];

		// Dual-write pattern: Sync to Typesense asynchronously
		if ($this->searchEnabled && $posttype !== 'webapp') {
			$this->syncToTypesense($post, $is_new_record);
		}

		return $id;
	}

	/**
	 * Sync object to Typesense search index
	 */
	private function syncToTypesense($object, $isNew = false)
	{
		if (!$this->searchEnabled) {
			return;
		}

		try {
			if ($isNew) {
				$result = $this->typesense->indexDocument($object);
			} else {
				$result = $this->typesense->updateDocument($object);
			}

			if (!$result) {
				// If sync fails, add to dead letter queue for retry
				$this->addToDeadLetterQueue($object, $isNew ? 'create' : 'update');
			}
		} catch (Exception $e) {
			error_log("Failed to sync object {$object['id']} to Typesense: " . $e->getMessage());
			$this->addToDeadLetterQueue($object, $isNew ? 'create' : 'update');
		}
	}

	/**
	 * Add failed sync operations to dead letter queue for retry
	 */
	private function addToDeadLetterQueue($object, $operation)
	{
		$sql = new MySQL();
		$queueData = [
			'id' => uniqid(),
			'type' => 'search_sync_failed',
			'object_id' => $object['id'],
			'object_type' => $object['type'],
			'operation' => $operation,
			'payload' => json_encode($object),
			'attempts' => 0,
			'max_attempts' => 5,
			'next_retry' => time() + 300, // Retry in 5 minutes
			'created_on' => time(),
			'updated_on' => time()
		];

		$sql->executeSQL("INSERT INTO `data` (`content`, `created_on`, `updated_on`) VALUES ('" . 
			mysqli_real_escape_string($sql->databaseLink, json_encode($queueData)) . 
			"', '" . time() . "', '" . time() . "')");
	}

	/**
	 * Process dead letter queue for failed search sync operations
	 */
	public function processSearchSyncQueue($batchSize = 50)
	{
		if (!$this->searchEnabled) {
			return;
		}

		$sql = new MySQL();
		$currentTime = time();

		// Get failed sync operations ready for retry
		$failedSyncs = $sql->executeSQL("
			SELECT * FROM `data` 
			WHERE `content`->>'$.type' = 'search_sync_failed' 
			AND `content`->>'$.next_retry' <= '$currentTime'
			AND `content`->>'$.attempts' < `content`->>'$.max_attempts'
			ORDER BY `created_on` ASC 
			LIMIT $batchSize
		");

		if (!$failedSyncs) {
			return;
		}

		foreach ($failedSyncs as $syncRecord) {
			$syncData = json_decode($syncRecord['content'], true);
			$attempts = (int)$syncData['attempts'] + 1;

			try {
				$object = json_decode($syncData['payload'], true);
				$success = false;

				switch ($syncData['operation']) {
					case 'create':
						$success = $this->typesense->indexDocument($object);
						break;
					case 'update':
						$success = $this->typesense->updateDocument($object);
						break;
					case 'delete':
						$success = $this->typesense->deleteDocument($syncData['object_id'], $syncData['object_type']);
						break;
				}

				if ($success) {
					// Remove from queue on success
					$sql->executeSQL("DELETE FROM `data` WHERE `id` = '{$syncRecord['id']}'");
				} else {
					// Update retry info
					$nextRetry = time() + (300 * $attempts); // Exponential backoff
					$sql->executeSQL("UPDATE `data` SET `content` = JSON_SET(`content`, 
						'$.attempts', '$attempts',
						'$.next_retry', '$nextRetry',
						'$.updated_on', '" . time() . "'
					) WHERE `id` = '{$syncRecord['id']}'");
				}

			} catch (Exception $e) {
				error_log("Queue processing error for sync ID {$syncRecord['id']}: " . $e->getMessage());
				
				// Update retry info
				$nextRetry = time() + (300 * $attempts);
				$sql->executeSQL("UPDATE `data` SET `content` = JSON_SET(`content`, 
					'$.attempts', '$attempts',
					'$.next_retry', '$nextRetry',
					'$.updated_on', '" . time() . "'
				) WHERE `id` = '{$syncRecord['id']}'");
			}
		}
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

		// Sync updated object to Typesense
		if ($this->searchEnabled) {
			$updatedObject = $this->getObject($id);
			if ($updatedObject && $updatedObject['type'] !== 'webapp') {
				$this->syncToTypesense($updatedObject, false);
			}
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
		else if ($identifier[0]['id'] ?? false) {
			$q = $sql->executeSQL("SELECT * from data
                where id IN (".implode( ",", array_column($identifier, 'id') ).")
                order by id desc
                limit 0,".count($identifier)
            );
		}

		//IF ARRAY HAS type and slugs
		else if (($identifier['type'] ?? false) && ($identifier['slugs'][0] ?? false)) {
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
		else if (($identifier[0]['type'] ?? false) && ($identifier[0]['slug'] ?? false)) {
			foreach ($identifier as $idn) {
				if ($idn['slug'] ?? null) {
					$_where[] = "(`type`='{$idn['type']}' AND `slug`='{$idn['slug']}')";
				} else {
					$_where[] = "(`type`='{$idn['type']}')";
				}
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
		$uploads = new Uploads();

		$types = $config->getTypes();

		if (!$id) {
			return false;
		}

		// Get object info before deletion for Typesense sync
		$objectToDelete = $this->getObject($id);
		$objectType = $this->getAttribute($id, 'type');

		$t = $this->getAttribute($id, 'type');
		if ($t == 'deleted_record') {
			if (($this->getAttribute($id, 'deleted_type') ?? '') == 'file_record') {
				$uploads->deleteFileRecord($this->getObject($id));
			}

			$q = $sql->executeSQL("DELETE FROM data WHERE id={$id}");
			
			// Remove from Typesense for permanent deletions
			if ($this->searchEnabled && $objectToDelete) {
				$deletedType = $this->getAttribute($id, 'deleted_type');
				if ($deletedType && $deletedType !== 'webapp') {
					try {
						$this->typesense->deleteDocument($id, $deletedType);
					} catch (Exception $e) {
						error_log("Failed to delete from Typesense: " . $e->getMessage());
						$this->addToDeadLetterQueue(['id' => $id, 'type' => $deletedType], 'delete');
					}
				}
			}
		}
		else if ($types['webapp']['soft_delete_records'] ?? false) {
			$sql->executeSQL("UPDATE data SET content = JSON_SET(content, '$.deleted_type', content->>'$.type', '$.type', 'deleted_record') WHERE id={$id}");
			
			// For soft deletes, remove from search index but keep in dead letter queue for potential restoration
			if ($this->searchEnabled && $objectType !== 'webapp') {
				try {
					$this->typesense->deleteDocument($id, $objectType);
				} catch (Exception $e) {
					error_log("Failed to delete from Typesense: " . $e->getMessage());
				}
			}
		} else {
			if ($t == 'file_record') {
				$uploads->deleteFileRecord($this->getObject($id));
			}

			$q = $sql->executeSQL("DELETE FROM data WHERE id={$id}");
			
			// Remove from Typesense for permanent deletions
			if ($this->searchEnabled && $objectType !== 'webapp') {
				try {
					$this->typesense->deleteDocument($id, $objectType);
				} catch (Exception $e) {
					error_log("Failed to delete from Typesense: " . $e->getMessage());
					$this->addToDeadLetterQueue(['id' => $id, 'type' => $objectType], 'delete');
				}
			}
		}

		return true;
	}

	public function deleteObjects(array $ids, string $redirect_type): bool
	{
		$sql = new MySQL;
		$config = new Config();

		$types = $config->getTypes();
		$t = $this->getAttribute($ids[0], 'type');
		$ids_string = implode(',', $ids);

		// Get objects info for Typesense sync
		$objectsToDelete = [];
		if ($this->searchEnabled && $t !== 'webapp') {
			$objectsToDelete = $this->getObjects($ids_string);
		}

		if ($t == 'deleted_record') {
			if (($this->getAttribute($ids[0], 'deleted_type') ?? '') == 'file_record') {
				$objects = $this->getObjects($ids_string);
				foreach ($objects as $object) {
					$uploads->deleteFileRecord($object);
				}
			}
			$sql->executeSQL("DELETE FROM data WHERE id IN ($ids_string)");
		}
		else if ($types['webapp']['soft_delete_records'] ?? false) {
			// soft delete
			$sql->executeSQL("UPDATE data SET content = JSON_SET(content, '$.deleted_type', content->>'$.type', '$.type', 'deleted_record') WHERE id IN ($ids_string)");
		} else {
			if ($t == 'file_record') {
				$objects = $this->getObjects($ids_string);
				foreach ($objects as $object) {
					$uploads->deleteFileRecord($object);
				}
			}
			// perma delete
			$sql->executeSQL("DELETE FROM data WHERE id IN ($ids_string)");
		}

		// Sync deletions to Typesense
		if ($this->searchEnabled && !empty($objectsToDelete)) {
			foreach ($objectsToDelete as $object) {
				if ($object['type'] !== 'webapp') {
					try {
						$this->typesense->deleteDocument($object['id'], $object['type']);
					} catch (Exception $e) {
						error_log("Failed to delete from Typesense: " . $e->getMessage());
						$this->addToDeadLetterQueue(['id' => $object['id'], 'type' => $object['type']], 'delete');
					}
				}
			}
		}

		return true;
	}

	/**
	 * Search objects using Typesense with fallback to database
	 */
	public function searchObjects($query, $options = [])
	{
		// Try Typesense first if enabled
		if ($this->searchEnabled && !empty(trim($query))) {
			try {
				$searchResults = $this->typesense->search($query, $options);
				
				if ($searchResults && !empty($searchResults['hits'])) {
					// Transform Typesense results to match expected format
					$ids = array_map(function($hit) {
						return ['id' => (int)$hit['document']['id']];
					}, $searchResults['hits']);
					
					// Get full objects from database to maintain data consistency
					$objects = $this->getObjects($ids);
					
					return [
						'objects' => $objects,
						'total_found' => $searchResults['found'] ?? 0,
						'search_time_ms' => $searchResults['search_time_ms'] ?? 0,
						'facet_counts' => $searchResults['facet_counts'] ?? [],
						'source' => 'typesense'
					];
				}
			} catch (Exception $e) {
				error_log("Typesense search failed, falling back to database: " . $e->getMessage());
			}
		}
		
		// Fallback to database search
		return $this->searchObjectsDatabase($query, $options);
	}

	/**
	 * Database-based search fallback
	 */
	private function searchObjectsDatabase($query, $options = [])
	{
		$type = $options['type'] ?? null;
		$limit = $options['limit'] ?? "0, 25";
		$sort_field = $options['sort_field'] ?? 'id';
		$sort_order = $options['sort_order'] ?? 'DESC';
		
		$search_array = ['type' => $type];
		
		// Simple full-text search across common fields
		if (!empty(trim($query))) {
			$search_array['search_terms'] = explode(' ', trim($query));
		}
		
		$ids = $this->getIDs($search_array, $limit, $sort_field, $sort_order, 
			$options['show_public_objects_only'] ?? true, [], true);
			
		if ($ids) {
			$objects = $this->getObjects($ids);
			$totalCount = $this->getIDsTotalCount($search_array, $limit, $sort_field, $sort_order, 
				$options['show_public_objects_only'] ?? true, [], true);
				
			return [
				'objects' => $objects,
				'total_found' => $totalCount,
				'search_time_ms' => 0,
				'facet_counts' => [],
				'source' => 'database'
			];
		}
		
		return [
			'objects' => [],
			'total_found' => 0,
			'search_time_ms' => 0,
			'facet_counts' => [],
			'source' => 'database'
		];
	}

	public function getIDs(
		array $search_arr,
		string $limit = "0, 25",
		string|array $sort_field = 'id',
		string|array $sort_order = 'DESC',
		bool $show_public_objects_only = true,
		array $ignore_ids = [],
		bool $show_partial_search_results = false,
		bool $show_case_sensitive_search_results = false,
		string|array $comparison_within_module_phrase = 'LIKE',
		string|array $inbetween_same_module_phrases = 'OR',
		string $between_different_module_phrases = 'AND',
		array $range = [],
		bool $debug_show_sql_statement = false)
	{
		$sql = new MySQL();
		
		$qry_vars = $this->getIDsQueryVars($search_arr, $limit, $sort_field, $sort_order, $show_public_objects_only, $ignore_ids, $show_partial_search_results, $show_case_sensitive_search_results, $comparison_within_module_phrase, $inbetween_same_module_phrases, $between_different_module_phrases, $range, $debug_show_sql_statement);
		$qry = $this->getIDsResultsQuery($qry_vars['search_arr'], $qry_vars['show_public_objects_only'], $qry_vars['ignore_ids'], $qry_vars['joint_modules_and_filters'], $qry_vars['priority'], $qry_vars['limit'], $qry_vars['debug_show_sql_statement']);

		$r = $sql->executeSQL($qry);
		return $r;
	}

	public function getIDsTotalCount(
		array $search_arr,
		string $limit = "0, 25",
		string|array $sort_field = 'id',
		string|array $sort_order = 'DESC',
		bool $show_public_objects_only = true,
		array $ignore_ids = [],
		bool $show_partial_search_results = false,
		bool $show_case_sensitive_search_results = false,
		string|array $comparison_within_module_phrase = 'LIKE',
		string|array $inbetween_same_module_phrases = 'OR',
		string $between_different_module_phrases = 'AND',
		array $range = [],
		bool $debug_show_sql_statement = false)
	{
		$sql = new MySQL();

		$qry_vars = $this->getIDsQueryVars($search_arr, $limit, $sort_field, $sort_order, $show_public_objects_only, $ignore_ids, $show_partial_search_results, $show_case_sensitive_search_results, $comparison_within_module_phrase, $inbetween_same_module_phrases, $between_different_module_phrases, $range, $debug_show_sql_statement);
		$qry = $this->getIDsTotalCountQuery($qry_vars['search_arr'], $qry_vars['show_public_objects_only'], $qry_vars['ignore_ids'], $qry_vars['joint_modules_and_filters'], $qry_vars['priority']);

		$r = $sql->executeSQL($qry);
		return $r[0]['count'];
	}

	private function getIDsQueryVars(
		array $search_arr,
		string $limit = "0, 25",
		string|array $sort_field = 'id',
		string|array $sort_order = 'DESC',
		bool $show_public_objects_only = true,
		array $ignore_ids = [],
		bool $show_partial_search_results = false,
		bool $show_case_sensitive_search_results = false,
		string|array $comparison_within_module_phrase = 'LIKE',
		string|array $inbetween_same_module_phrases = 'OR',
		string $between_different_module_phrases = 'AND',
		array $range = [],
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
		else if ($sort_field == '(random)') {
			$priority = "RAND()";
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
					if ($kv != 'type' && trim($vv) != "" && trim($vv) !== false) {
						if ($show_case_sensitive_search_results)
							$query_phrases_temp[] = "`content`->>'$." . $key . "' " . $comparison_within_module_phrase_arr[$i] . " " . (trim($vv) ? "'" . ($show_partial_search_results?"%":"") . $vv . ($show_partial_search_results?"%":"") . "'" : "");
						else
							$query_phrases_temp[] = "LOWER(`content`->>'$." . $key . "') " . $comparison_within_module_phrase_arr[$i] . " " . (trim($vv) ? "'" . ($show_partial_search_results?"%":"") . strtolower($vv) . ($show_partial_search_results?"%":"") . "'" : "");
					}
				}
				$query_phrases[] = '('.join(' ' . $inbetween_same_module_phrases_arr[$i] . ' ', $query_phrases_temp).')';
			} else {
				if ($key != 'type' && trim($value) != "" && trim($value) !== false) {
					if ($show_case_sensitive_search_results)
						$query_phrases[] = "`content`->>'$." . $key . "' " . $comparison_within_module_phrase_arr[$i] . " " . (trim($value) ? "'" . ($show_partial_search_results?"%":"") . $value . ($show_partial_search_results?"%":"") . "'" : "");
					else
						$query_phrases[] = "LOWER(`content`->>'$." . $key . "') " . $comparison_within_module_phrase_arr[$i] . " " . (trim($value) ? "'" . ($show_partial_search_results?"%":"") . strtolower($value) . ($show_partial_search_results?"%":"") . "'" : "");
				}
			}

			$i++;
		}

		$joint_modules_and_filters = trim(join(' ' . $between_different_module_phrases . ' ', $query_phrases));

		if (count($range) >= 1) {
			$range_statement_qrys = [];
			foreach ($range as $key => $value) {
				if ($value['from'] ?? false)
					$range_statement_qrys[] = "LOWER(`content`->>'$." . $key . "') >= '" . $value['from'] . "'";
				if ($value['to'] ?? false)
					$range_statement_qrys[] = "LOWER(`content`->>'$." . $key . "') <= '" . $value['to'] . "'";
			}

			$range_statement = implode(' AND ', $range_statement_qrys );

			$joint_modules_and_filters .= ' '.$between_different_module_phrases.' ( '.$range_statement.' ) ';
		}

		return array(
			'search_arr'=>$search_arr,
			'show_public_objects_only'=>$show_public_objects_only,
			'ignore_ids'=>$ignore_ids,
			'joint_modules_and_filters'=>$joint_modules_and_filters,
			'priority'=>$priority,
			'limit'=>$limit,
			'debug_show_sql_statement'=>$debug_show_sql_statement,
		);
	}

	private function getIDsResultsQuery($search_arr, $show_public_objects_only, $ignore_ids, $joint_modules_and_filters, $priority, $limit, $debug_show_sql_statement) {
		$qry = "SELECT `id` FROM `data` WHERE " . 
			($search_arr['type']!='user' ? ($show_public_objects_only !==  false ? "`content_privacy`='public' AND `type`='".$search_arr['type']."'" : "`type`='".$search_arr['type']."'"):"`type`='".$search_arr['type']."'") . 
			($joint_modules_and_filters ? ' AND '.$joint_modules_and_filters : "") . 
			(($ignore_ids != [] && count($ignore_ids) > 0) ? " AND `id` NOT IN ('".implode("', '", $ignore_ids)."')" : "") . 
			" ORDER BY " . $priority . 
			($limit ? " LIMIT " . $limit : "");

		if ($debug_show_sql_statement) {
			echo $qry;
		}

		return $qry;

	}

	private function getIDsTotalCountQuery($search_arr, $show_public_objects_only, $ignore_ids, $joint_modules_and_filters, $priority) {
		$qry = "SELECT COUNT(`id`) AS `count` FROM `data` WHERE " . 
			($search_arr['type']!='user' ? ($show_public_objects_only !==  false ? "`content_privacy`='public' AND `type`='".$search_arr['type']."'" : "`type`='".$search_arr['type']."'"):"`type`='".$search_arr['type']."'") . 
			(($ignore_ids != [] && count($ignore_ids) > 0) ? " AND `id` NOT IN ('".implode("', '", $ignore_ids)."')" : "") . 
			($joint_modules_and_filters ? ' AND '.$joint_modules_and_filters : "") . 
			" ORDER BY " . $priority;

		return $qry;
	}
}