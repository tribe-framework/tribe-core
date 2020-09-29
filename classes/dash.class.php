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

	function get_ids_by_search_query ($query) {
		global $sql;
		return $sql->executeSQL("SELECT `id` FROM `data` WHERE LOWER(`content`->'$.view_searchable_data') LIKE '%".strtolower(urldecode($query))."%' && `content`->'$.content_privacy'='public' GROUP BY `id` LIMIT 25");
	}

	function push_content ($post) {
		global $sql, $types;
		$updated_on=time();
		$posttype=$post['type'];

		$title_data=$this->get_type_title_data($post);
		$title_slug=$title_data['slug'];
		$title_unique=$title_data['unique'];

		//loop that doesn't break
		$post['view_searchable_data']='';
		foreach ($types[$posttype]['modules'] as $module) {
			//password md5 handling is a tricky game
			//connected to admin/edit.php
			//$this->get_content can mess up passwords
			if ($module['input_type']=='password') {
				$password_slug=$module['input_slug'];
				$password_slug_md5=$module['input_slug'].'_md5';

				if ($post[$password_slug] && !$post[$password_slug_md5]) {
					if ($post['id']) //while importing from get_content function
						$post[$password_slug]=$post[$password_slug];
					else //for new entries
						$post[$password_slug]=md5($post[$password_slug]);
				}
				else if ($post[$password_slug] && (md5($post[$password_slug])!=$post[$password_slug_md5])) { //post edit, password changed
					$post[$password_slug]=md5($post[$password_slug]);
				}
				else if ($post[$password_slug_md5]) { //post edit, when password unchanged
					$post[$password_slug]=$post[$password_slug_md5];
					unset($post[$password_slug_md5]);
				}
			}

			if ($module['view_searchable'] && in_array($post['type'], $types['webapp']['searchable_types']) && $post['content_privacy']=='public') {
				$slug=$module['input_slug'];
				if (is_array($post[$slug]))
					$post['view_searchable_data'].=implode(' ', array_map('strip_tags', $post[$slug])).' ';
				else
					$post['view_searchable_data'].=strip_tags($post[$slug]).' ';
			}
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

		if (!trim($post['view_searchable_data']))
			$this->push_content_meta($post['id'], 'view_searchable_data');

		dash::$last_info[]='Content saved.';
		dash::$last_data[]=array('updated_on'=>$updated_on, 'id'=>$id, 'slug'=>$post['slug'], 'url'=>BASE_URL.'/'.$post['type'].'/'.$post['slug']);
		return $id;
	}

	function get_content_meta ($val, $meta_key) {
		global $sql;

		if ($meta_key=='id' || $meta_key=='updated_on' || $meta_key=='created_on')
			$qry="`".$meta_key."`";
		else
			$qry="`content`->>'$.".$meta_key."' `".$meta_key."`";

		if (is_numeric($val))
			$q=$sql->executeSQL("SELECT ".$qry." FROM `data` WHERE `id`='$val'");
		else
			$q=$sql->executeSQL("SELECT ".$qry." FROM `data` WHERE `content`->'$.slug'='".$val['slug']."' && `content`->'$.type'='".$val['type']."'");

		return $q[0][$meta_key];
	}

	function push_content_meta ($id, $meta_key, $meta_value='') {
		global $sql;
		if ($id && $meta_key) {
			if (!trim($meta_value)) {
				//to delete a key, leave it empty
				$q=$sql->executeSQL("UPDATE `data` SET `content` = JSON_REMOVE(`content`, '$.".$meta_key."') WHERE `id`='$id'");
			}
			else {
				$q=$sql->executeSQL("UPDATE `data` SET `content` = JSON_SET(`content`, '$.".$meta_key."', '$meta_value') WHERE `id`='$id'");
			}
			return 1;
		}
		else
			return 0;
	}

	function get_content ($val) {
		global $sql, $session_user;
		$or=array();
		if (is_numeric($val))
			$q=$sql->executeSQL("SELECT * FROM `data` WHERE `id`='$val'");
		else
			$q=$sql->executeSQL("SELECT * FROM `data` WHERE `content`->'$.slug'='".$val['slug']."' && `content`->'$.type'='".$val['type']."'");
		if ($q[0]['id']) {
			$or=json_decode($q[0]['content'], true);
			$or['id']=$q[0]['id'];
			$or['updated_on']=$q[0]['updated_on'];
			$or['created_on']=$q[0]['created_on'];

			if ($or['content_privacy']=='draft') {
				if ($session_user['user_id']==$or['user_id'])
					return $or;
				else
					return 0;
			}
			else if ($or['content_privacy']=='pending') {
				if ($session_user['role']=='admin' || $session_user['user_id']==$or['user_id'])
					return $or;
				else
					return 0;
			}
			else
				return $or;
		}
		else
			return 0;
	}

	function fetch_content_title_array ($slug, $column_key, $with_link=1) {
		global $types, $sql;
		$q=$sql->executeSQL("SELECT `content`->'$.title' `title` FROM `data` WHERE `content`->'$.type'='$column_key' && `content`->'$.slug'='$slug'");
		if ($with_link)
			return '<a href="'.BASE_URL.'/'.$column_key.'/'.$slug.'">'.json_decode($q[0]['title']).'</a>';
		else
			return json_decode($q[0]['title']);
	}

	public static function get_all_ids_count ($type) {

		global $sql, $session_user;

		//user
		if (is_array($type)) {
			//accessible only to admins
			if ($session_user['role']!='admin')
				return 0;
			else {
				$role_slug=$type['role_slug'];
				$type=$type['type'];
				$q=$sql->executeSQL("SELECT `id` FROM `data` WHERE `content`->'$.type'='$type' ".($role_slug?"&& `content`->'$.role_slug'='$role_slug'":""));
			}
		}

		//content
		else {
			$role_slug='';
			if ($session_user['role']=='admin') {
				$q=$sql->executeSQL("SELECT `id` FROM `data` WHERE (`content`->'$.content_privacy'!='draft' OR `content`->'$.user_id'='".$session_user['user_id']."') && `content`->'$.type'='$type' ".($role_slug?"&& `content`->'$.role_slug'='$role_slug'":""));
			}
			else {
				$q=$sql->executeSQL("SELECT `id` FROM `data` WHERE (`content`->'$.content_privacy'='public' OR `content`->'$.user_id'='".$session_user['user_id']."') && `content`->'$.type'='$type' ".($role_slug?"&& `content`->'$.role_slug'='$role_slug'":""));
			}
		}

		return $sql->records;
	}

	public static function get_all_ids ($type, $priority_field='id', $priority_order='DESC', $limit='') {
		global $sql, $session_user;

		if ($priority_field=='id')
			$priority="`".$priority_field."` ".$priority_order;
		else
			$priority="`content`->'$.".$priority_field."' IS NULL, `content`->'$.".$priority_field."' ".$priority_order.", `id` DESC";

		//user
		if (is_array($type)) {
			//accessible only to admins
			if ($session_user['role']!='admin')
				return 0;
			else {
				$role_slug=$type['role_slug'];
				$type=$type['type'];
				$q=$sql->executeSQL("SELECT `id` FROM `data` WHERE `content`->'$.type'='$type' ".($role_slug?"&& `content`->'$.role_slug'='$role_slug'":"")." ORDER BY ".$priority.($limit?" LIMIT ".$limit:""));
			}
		}

		//content
		else {
			$role_slug='';
			if ($session_user['role']=='admin') {
				$q=$sql->executeSQL("SELECT `id` FROM `data` WHERE (`content`->'$.content_privacy'!='draft' OR `content`->'$.user_id'='".$session_user['user_id']."') && `content`->'$.type'='$type' ".($role_slug?"&& `content`->'$.role_slug'='$role_slug'":"")." ORDER BY ".$priority.($limit?" LIMIT ".$limit:""));
			}
			else {
				$q=$sql->executeSQL("SELECT `id` FROM `data` WHERE (`content`->'$.content_privacy'='public' OR `content`->'$.user_id'='".$session_user['user_id']."') && `content`->'$.type'='$type' ".($role_slug?"&& `content`->'$.role_slug'='$role_slug'":"")." ORDER BY ".$priority.($limit?" LIMIT ".$limit:""));
			}
		}

		return $q;
	}

	function get_ids ($search_arr, $comparison='LIKE', $between='||', $priority_field='id', $priority_order='DESC', $limit='', $debug_show_sql_statement=0) {
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
			if (is_array($value)) {
				foreach ($value as $kv => $vv)
					$frechr[]="`content`->'$.".$kv."' ".$comparisonr[$i]." ".(trim($vv)?"'".$vv."'":"");
			}
			else
				$frechr[]="`content`->'$.".$key."' ".$comparisonr[$i]." ".(trim($value)?"'".$value."'":"");
			$i++;
		}

		$qry="SELECT `id` FROM `data` WHERE `content`->'$.content_privacy'='public' AND ".join(' '.$between.' ', $frechr)." ORDER BY ".$priority.($limit?" LIMIT ".$limit:"");
		$r=$sql->executeSQL($qry);
		if ($debug_show_sql_statement)
			echo $qry;
		return $r;
	}

	function get_date_ids ($publishing_date) {
		global $sql;
		return $sql->executeSQL("SELECT `id` FROM `data` WHERE `content`->'$.publishing_date'='$publishing_date'");
	}

	function do_slugify ($string, $input_itself_is_unique=0) {
		$slug = substr(strtolower(trim(preg_replace('/[^A-Za-z0-9_-]+/', '-', ($string?$string:'untitled')))),0,1500).($input_itself_is_unique?'':'-'.uniqid());
		return $slug;
	}

	function do_unslugify ($url_part) {
		return strtolower(trim(urlencode($url_part)));
	}

	public static function get_types ($json_path) {
		global $session_user, $userless_install;

		$types=json_decode(file_get_contents($json_path), true);
		foreach ($types as $key=>$type) {
			if (($type['type']??'')=='content') {
				if (!in_array('content_privacy', array_column($types[$key]['modules'], 'input_slug'))) {
					if ($session_user['role']=='admin' || $userless_install) {
						$content_privacy_json='{
					        "input_slug": "content_privacy",
					        "input_placeholder": "Content privacy",
					        "input_type": "select",
					        "input_options": [
					          {"slug":"public", "title":"Public link"},
					          {"slug":"private", "title":"Private link"},
					          {"slug":"pending", "title":"Submit for moderation"},
					          {"slug":"draft", "title":"Draft"}
					        ],
					        "list_field": true,
					        "input_unique": false
					    }';
			  		}
					else {
						$content_privacy_json='{
					        "input_slug": "content_privacy",
					        "input_placeholder": "Content privacy",
					        "input_type": "select",
					        "input_options": [
					          {"slug":"pending", "title":"Submit for moderation"},
					          {"slug":"draft", "title":"Draft"}
					        ],
					        "list_field": true,
					        "input_unique": false
					    }';
			  		}
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

	function get_type_title_data ($post) {
		global $sql, $types;
		$posttype=$post['type'];

		if (!($post_id=$post['id'])) {
			$last_id=$sql->executeSQL("SELECT `id` FROM `data` ORDER BY `id` DESC LIMIT 1");
			$post_id=$last_id[0]['id']+1;
		}
		//foreach loop that breaks
		$i=0;
		foreach ($types[$posttype]['modules'] as $module) {
			$title = array();
			if ($module['input_primary'] && (!$module['restrict_id_max'] || $post_id<=$module['restrict_id_max']) && (!$module['restrict_id_min'] || $post_id>=$module['restrict_id_min'])) {
				$title_id=$i;
				$title['slug']=$module['input_slug'].(is_array($module['input_lang'])?'_'.$module['input_lang'][0]['slug']:'');
				$title['primary']=$module['input_primary'];
				$title['unique']=$module['input_unique'];
				break;
			}

			$i++;
		}
		return $title;
	}

	function push_wp_posts ($type='story', $meta_vars=array(), $max_records=0, $overwrite=0) {
		global $sql;
		$i=0;

		$q=$sql->executeSQL("SELECT * FROM `wp_posts` WHERE `post_status` LIKE 'publish' AND (`post_type` LIKE 'page' OR `post_type` LIKE 'post') ORDER BY `ID` ASC");

		foreach ($q as $r) {
			if ($overwrite || !$this->get_content_meta($r['ID'], 'slug')) {
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
			    //$post=update_wp_post_data($post);

			    $this->push_content($post);

			    $i++;
				if ($max_records && $i>=$max_records)
					break;
			}
		}
	}

	function get_unique_user_id () {
		global $sql;
		$bytes = strtoupper(bin2hex(random_bytes(3)));

		$q=$sql->executeSQL("SELECT `id` FROM `data` WHERE `content`->'$.user_id'='$bytes' && `content`->'$.type'='user'");
		if ($q[0]['id'])
			return $this->get_unique_user_id();
		else
			return $bytes;
	}

	function do_shell_command ($cmd) {
		ob_start();
		passthru($cmd);
		$tml = ob_get_contents();
		ob_end_clean();
		return $tml;
	}

	function get_upload_dir_path () {
		return ABSOLUTE_PATH.'/uploads/'.date('Y').'/'.date('m-F').'/'.date('d-D');
	}

	function get_upload_dir_url () {
		return BASE_URL.'/uploads/'.date('Y').'/'.date('m-F').'/'.date('d-D');
	}

	function after_login ($roleslug, $redirect_url='') { 
		global $types, $_SESSION, $user;

		$user['role']=$types['user']['roles'][$roleslug]['role'];
		
		//for admin and crew (staff)
		if ($types['user']['roles'][$roleslug]['role']=='admin' || $types['user']['roles'][$roleslug]['role']=='crew') {
			$_SESSION['user_id']=$user['user_id'];
			$_SESSION['email']=$user['email'];
			$_SESSION['user']=$user;
			$_SESSION['wildfire_dashboard_access']=1;
			ob_start(); header('Location: '.($redirect_url??'/admin'));
		}

		//for members
		else if ($types['user']['roles'][$roleslug]['role']=='member') {
			$_SESSION['user_id']=$user['user_id'];
			$_SESSION['email']=$user['email'];
			$_SESSION['user']=$user;
			$_SESSION['wildfire_dashboard_access']=0;
			ob_start(); header('Location: '.($redirect_url??'/user'));
		}

		//for visitors and anybody else
		else {
			ob_start(); header('Location: '.($redirect_url??'/'));
		}
	}

	function get_uploader_path () {
		$folder_path='uploads/'.date('Y').'/'.date('m-F').'/'.date('d-D');
		if (!is_dir(ABSOLUTE_PATH.'/'.$folder_path))
			mkdir(ABSOLUTE_PATH.'/'.$folder_path, 0755, true);
		return array('upload_dir'=>ABSOLUTE_PATH.'/'.$folder_path, 'upload_url'=>BASE_URL.'/'.$folder_path);
	}
}
?>