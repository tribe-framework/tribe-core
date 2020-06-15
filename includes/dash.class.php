<?php
/*
	functions start with push_, pull_, get_, do_ or is_
	push_ is to save to database
	pull_ is to pull from database, returns 1 or 0, saves the output array in $last_data
	get_ is to get usable values from functions
	do_ is for action that doesn't have a database push or pull
	is_ is for a yes/no answer
*/

class dash {  

	public static $last_error = null; //array of error messages
	public static $last_info = null; //array of info messages
	public static $last_data = null; //array of data to be sent for display
	public static $last_redirect = null; //redirection url

	function __construct () {
		
	}

	function get_last_error () {
		if (count(dash::$last_error)) {
			$op=implode('<br>', dash::$last_error);
			dash::$last_error=array();
			return $op;
		}
		else
			return '';
	}

	function get_last_info () {
		if (count(dash::$last_info)) {
			$op=implode('<br>', dash::$last_info);
			dash::$last_info=array();
			return $op;
		}
		else
			return '';
	}

	function get_last_data () {
		$arr=dash::$last_data;
		dash::$last_data=array();
		return $arr;
	}

	function get_last_redirect () {
		$r=dash::$last_redirect;
		dash::$last_redirect='';
		return $r;
	}

	function get_next_id () {
		global $sql;
		$q=$sql->executeSQL("SELECT `id` FROM `data` WHERE 1 ORDER BY `id` DESC LIMIT 1");
		return ($q[0]['id']+1);
	}

	function do_delete ($post=array()) {
		global $sql;
		$q=$sql->executeSQL("DELETE FROM `data` WHERE `id`='".$post['id']."'");
		dash::$last_redirect='/admin/list?type='.$post['type'];
		return 1;
	}

	function push_content ($post) {
		global $sql, $types;
		$updated_on=time();
		$posttype=$post['type'];

		$i=0;
		foreach ($types[$posttype]['modules'] as $module) {
			if ($module['input_primary'] && (!$module['restrict_id_max'] || $post['id']<=$module['restrict_id_max']) && (!$module['restrict_id_min'] || $post['id']>=$module['restrict_id_min'])) {
				$title_id=$i;
				$title_slug=$module['input_slug'].(is_array($module['input_lang'])?'_'.$module['input_lang'][0]['slug']:'');
				$title_primary=$module['input_primary'];
				$title_unique=$module['input_unique'];
				break;
			}
			$i++;
		}

		foreach ($types[$posttype]['modules'] as $module) {
			if ($module['input_type']=='password') {
				$password_slug=$module['input_slug'];
				$password_slug_md5=$module['input_slug'].'_md5';
				if ($post[$password_slug])
					$post[$password_slug]=md5($post[$password_slug]);
				else if ($post[$password_slug_md5]) {
					$post[$password_slug]=$post[$password_slug_md5];
					unset($post[$password_slug_md5]);
				}
			}
			$i++;
		}

		if ($title_unique) {
			$q=$sql->executeSQL("SELECT `id` FROM `data` WHERE `content`->'$.type'='".$post['type']."' && `content`->'$.".$title_slug."'='".$post[$title_slug]."'");
			if ($q[0]['id'] && $post['id']!=$q[0]['id']) {
				dash::$last_error[]='Either the title is left empty or the same title already exists in '.$types[$posttype]['plural'];
				return 0;
			}
		}

		if (!trim($post['slug']) || $post['slug_update']) {
			$post['slug']=dash::do_slugify($post[$title_slug], $title_unique);
		}

		if (!trim($post['id'])) {
			$sql->executeSQL("INSERT INTO `data` (`created_on`) VALUES ('$updated_on')");
			$post['id']=$sql->lastInsertID();
		}

		if ($post['wp_import']) {
			$sql->executeSQL("INSERT INTO `data` (`id`, `created_on`) VALUES ('".$post['id']."', '$updated_on')");
		}

		$sql->executeSQL("UPDATE `data` SET `content`='".mysqli_real_escape_string($sql->databaseLink, json_encode($post))."', `updated_on`='$updated_on' WHERE `id`='".$post['id']."'");
		$id=$post['id'];

		dash::$last_info[]='Content saved.';
		dash::$last_data[]=array('updated_on'=>$updated_on, 'id'=>$id, 'slug'=>$post['slug'], 'url'=>BASE_URL.'/'.$post['type'].'/'.$post['slug']);
		return $id;
	}

	function get_content_meta ($id, $meta_key) {
		global $sql;
		$q=$sql->executeSQL("SELECT * FROM `data` WHERE `id`='$id'");
		$or=json_decode($q[0]['content'], true);
		return $or[$meta_key];
	}

	function push_content_meta ($id, $meta_key, $meta_value) {
		global $sql;
		if ($id && $meta_key) {
			if (!trim($meta_value)) {
				echo "UPDATE `data` SET `content` = JSON_REMOVE(`content`, '$.".$meta_key."') WHERE `id`='$id'";
				$q=$sql->executeSQL("UPDATE `data` SET `content` = JSON_REMOVE(`content`, '$.".$meta_key."') WHERE `id`='$id'");
			}
			else
				$q=$sql->executeSQL("UPDATE `data` SET `content` = JSON_SET(`content`, '$.".$meta_key."', '$meta_value') WHERE `id`='$id'");
			return 1;
		}
		else
			return 0;
	}

	function get_content ($val) {
		global $sql;
		$or=array();
		if (is_numeric($val))
			$q=$sql->executeSQL("SELECT * FROM `data` WHERE `id`='$val'");
		else 
			$q=$sql->executeSQL("SELECT * FROM `data` WHERE `content`->'$.slug'='".$val['slug']."' && `content`->'$.type'='".$val['type']."'");
		$or=array_merge(json_decode($q[0]['content'], true), $q[0]);
		return $or;
	}
	
	function fetch_content_title_array ($slug, $column_key, $with_link=1) {
		global $types, $sql;
		$q=$sql->executeSQL("SELECT `content`->'$.title' `title` FROM `data` WHERE `content`->'$.type'='$column_key' && `content`->'$.slug'='$slug'");
		if ($with_link)
			return '<a href="'.BASE_URL.'/'.$column_key.'/'.$slug.'">'.json_decode($q[0]['title']).'</a>';
		else
			return json_decode($q[0]['title']);
	}

	function get_all_ids ($type, $priority_field='id', $priority_order='DESC', $limit='') {
		global $sql;
		if (is_array($type)) {
			$role_slug=$type['role_slug'];
			$type=$type['type'];
		}
		else
			$role_slug='';

		if ($priority_field=='id')
			$priority="`".$priority_field."` ".$priority_order;
		else
			$priority="`content`->'$.".$priority_field."' IS NULL, `content`->'$.".$priority_field."' ".$priority_order.", `id` DESC";
		return $sql->executeSQL("SELECT `id` FROM `data` WHERE `content`->'$.type'='$type' ".($role_slug?"&& `content`->'$.role_slug'='$role_slug'":"")." ORDER BY ".$priority.($limit?" LIMIT ".$limit:""));
	}

	function get_ids ($search_arr, $comparison='LIKE', $between='||', $priority_field='id', $priority_order='DESC', $limit='') {
		global $sql;
		if ($priority_field=='id')
			$priority="`".$priority_field."` ".$priority_order;
		else
			$priority="`content`->'$.".$priority_field."' IS NULL, `content`->'$.".$priority_field."' ".$priority_order.", `id` DESC";
		$frechr=array();
		$i=0;
		if (!is_array($comparison))
			$comparisonr=array_fill(0, count($search_arr), $comparison);
		else
			$comparisonr=$comparison;
		foreach ($search_arr as $key => $value) {
			$frechr[]="`content`->'$.".$key."' ".$comparison[$i]." ".(trim($value)?"'".$value."'":"");
			$i++;
		}
		$r=$sql->executeSQL("SELECT `id` FROM `data` WHERE ".join(' '.$between.' ', $frechr)." ORDER BY ".$priority.($limit?" LIMIT ".$limit:""));
		return $r; 
	}

	function get_date_ids ($publishing_date) {
		global $sql;
		return $sql->executeSQL("SELECT `id` FROM `data` WHERE `content`->'$.publishing_date'='$publishing_date'");
	}

	function do_slugify ($string, $input_itself_is_unique=0) {
		$slug = strtolower(trim(preg_replace('/[^A-Za-z0-9_-]+/', '-', ($string?$string:'untitled')))).($input_itself_is_unique?'':'-'.uniqid());
		return $slug;
	}
	
	function do_unslugify ($url_part) {
		return strtolower(trim(urlencode($url_part)));
	}

	function get_types ($json_path) {
		$types=json_decode(file_get_contents($json_path), true);
		foreach ($types as $key=>$type) {
			if ($type['type']=='content') {
				if (!in_array('content_privacy', array_column($types[$key]['modules'], 'input_slug'))) {
				$content_privacy_json='{
			        "input_slug": "content_privacy",
			        "input_placeholder": "Content privacy",
			        "input_type": "select",
			        "input_options": [
			          {"slug":"public", "title":"Public link"},
			          {"slug":"private", "title":"Private link"},
			          {"slug":"draft", "title":"Draft"}
			        ],
			        "list_field": true,
			        "input_unique": false
			      }';
				$types[$key]['modules'][]=json_decode($content_privacy_json, true);
		  		}

		  		foreach ($types[$key]['modules'] as $module) {
		  			if ($module['input_primary']) {
		  				$types[$key]['primary_module']=$module['input_slug'];
		  				break;
		  			}
		  		}
		  	}
		}
		return $types;
	}

	function push_wp_posts ($type='story', $meta_vars=array(), $max_records=0) {
		global $sql;
		$i=0;

		$q=$sql->executeSQL("SELECT * FROM `wp_posts` WHERE `post_status` LIKE 'publish' AND (`post_type` LIKE 'page' OR `post_type` LIKE 'post') ORDER BY `ID` ASC");
		
		foreach ($q as $r) {
			if (!$this->get_content_meta($r['ID'], 'slug')) {
				$post=$post_wp=array();
				$post['wp_import']=1;
			    $post['id']=$r['ID'];
			    $post['type']=$type;
			    $post['title']=$r['post_title'];
			    $post['body']=$r['post_content'];
			    $post['slug']=$r['post_name'];
			    $post['content_privacy']='public';
			    $post['publishing_date']=substr($r['post_date'], 0, 10);

				if ($r['post_parent']) {
				    $mv=$sql->executeSQL("SELECT `post_name` FROM `wp_posts` WHERE `ID`='".$r['post_parent']."'");
				    $post_wp['post_parent']=$mv[0]['post_name'];
				}

				foreach ($meta_vars as $var) {
					$iv=$sql->executeSQL("SELECT `meta_value` FROM `wp_postmeta` WHERE `post_id`='".$r['ID']."' && `meta_key`='$var'");
					if ($iv[0]['meta_value']) {
						$ivts=unserialize($iv[0]['meta_value']);
						if (!$ivts) {
								$iid=$iv[0]['meta_value'];
								if (is_numeric($iid)) {
									$mv=$sql->executeSQL("SELECT `post_name` FROM `wp_posts` WHERE `ID`='$iid'");
							    	$post_wp[$var]=$mv[0]['post_name'];
							    }
							    else
									$post_wp[$var]=$iid;
						}
						else {
							foreach ($ivts as $iid) {
								if (is_numeric($iid)) {
									$mv=$sql->executeSQL("SELECT `post_name` FROM `wp_posts` WHERE `ID`='$iid'");
							    	$post_wp[$var][]=$mv[0]['post_name'];
							    }
							    else
									$post_wp[$var][]=$iid;
							}
						}
					}
				}
			    
			    $cv=$sql->executeSQL("SELECT `guid` FROM `wp_posts` WHERE `post_parent` != 0 AND `guid` LIKE '%wp-content/uploads%' AND `post_type` LIKE 'attachment' AND `post_status` LIKE 'inherit' AND `guid` != '' AND `post_parent`='".$r['ID']."' ORDER BY `ID` DESC");
			    $post['files']=array();
			    foreach ($cv as $file) {
			    	$ext=strtolower(pathinfo($file['guid'], PATHINFO_EXTENSION));
			    	if ($ext=='png' || $ext=='jpg' || $ext=='jpeg' || $ext=='gif') {
			    		$post['files'][]=$file['guid'];
			    		$post['cover_media']=$file['guid'];
			    	}
			    	else {
			    		$post['files'][]=$file['guid'];
			    	}
			    }

			    $post['wp_post_data']=serialize($post_wp);
			    $post=update_wp_post_data($post);
			    
			    $this->push_content($post);

			    $i++;
				if ($max_records && $i>=$max_records)
					break;
			}
		}
	}

	function get_unique_user_id () {
		$bytes = random_bytes(3);
		return strtoupper(bin2hex($bytes));
	}
}
?>