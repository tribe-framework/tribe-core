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

		if ($types[$posttype]['modules'][0]['input_unique']) {
			$q=$sql->executeSQL("SELECT `id` FROM `data` WHERE `content`->'$.type'='".$post['type']."' && `content`->'$.title'='".$post['title']."'");
			if ($q[0]['id'] && $post['id']!=$q[0]['id']) {
				dash::$last_error[]='Either the title is left empty or the same title already exists in '.$types[$posttype]['plural'];
				return 0;
			}
		}

		if (!trim($post['id'])) {
			$sql->executeSQL("INSERT INTO `data` (`created_on`, `user_id`) VALUES ('$updated_on', '1')");
			$post['id']=$sql->lastInsertID();
		}

		if (!trim($post['slug']) || $post['slug_update']) {
			$post['slug']=dash::do_slugify($post['title'], $types[$posttype]['modules'][0]['input_unique']);
		}

		$sql->executeSQL("UPDATE `data` SET `content`='".mysqli_real_escape_string($sql->databaseLink, json_encode($post))."', `updated_on`='$updated_on' WHERE `id`='".$post['id']."'");
		$id=$post['id'];

		dash::$last_info[]='Content saved.';
		dash::$last_data[]=array('updated_on'=>$updated_on, 'id'=>$id, 'slug'=>$post['slug']);
		return 1;
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

	function get_all_ids ($type) {
		global $sql;
		return $sql->executeSQL("SELECT `id` FROM `data` WHERE `content`->'$.type'='$type' ORDER BY `id` DESC");
	}

	function get_date_ids ($publishing_date) {
		global $sql;
		return $sql->executeSQL("SELECT `id` FROM `data` WHERE `content`->'$.publishing_date'='$publishing_date'");
	}

	function do_slugify ($string, $input_itself_is_unique=0) {
		$slug = strtolower(trim(preg_replace('/[^A-Za-z0-9_-]+/', '-', ($string?$string:'untitled')))).($input_itself_is_unique?'':'-'.uniqid());
		return $slug;
	}
	
	function get_types ($json_path) {
		$types=json_decode(file_get_contents($json_path), true);
		foreach ($types as $key=>$type) {
			if ($type['type']=='content' && !in_array('content_privacy', array_column($types[$key]['modules'], 'input_slug'))) {
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
		}
		return $types;
	}
}
?>