<?php
/*
	functions start with push_, pull_, get_, do_ or is_
	push_ is to save to database
	pull_ is to pull from database, returns 1 or 0, saves the output array in $last_data
	get_ is to get usable values from functions
	do_ is for action that doesn't have a database push or pull
	is_ is for a yes/no answer
*/

class auth {  

	public static $last_error = null; //array of error messages
	public static $last_info = null; //array of info messages
	public static $last_data = null; //array of data to be sent for display
	public static $last_redirect = null; //redirection url

	function __construct () {
		
	}

	function get_last_error () {
		if (count(auth::$last_error)) {
			$op=implode('<br>', auth::$last_error);
			auth::$last_error=array();
			return $op;
		}
		else
			return '';
	}

	function get_last_info () {
		if (count(auth::$last_info)) {
			$op=implode('<br>', auth::$last_info);
			auth::$last_info=array();
			return $op;
		}
		else
			return '';
	}

	function get_last_data () {
		$arr=auth::$last_data;
		auth::$last_data=array();
		return $arr;
	}

	function get_last_redirect () {
		$r=auth::$last_redirect;
		auth::$last_redirect='';
		return $r;
	}

	function push_user ($post) {
		global $sql;
		$updated_on=time();

		$q=$sql->executeSQL("SELECT `id` FROM `auth` WHERE `user`->'$.type'='".$post['type']."' && `user`->'$.unique_id'='".$post['unique_id']."'");

		if (!trim($post['id']) && $q[0]['id']) {
			auth::$last_error[]='Same unique_id exists in this user type.';
			return 0;
		}
		else {
			if (!trim($post['id'])) {
				$sql->executeSQL("INSERT INTO `auth` (`created_on`) VALUES ('$updated_on')");
				$post['id']=$sql->lastInsertID();
			}

			$sql->executeSQL("UPDATE `auth` SET `user`='".json_encode($post)."', `updated_on`='$updated_on' WHERE `id`='".$post['id']."'");
			$id=$post['id'];

			auth::$last_info[]='User saved.';
			auth::$last_data[]=array('updated_on'=>$updated_on, 'id'=>$id);
			return 1;
		}
	}

	function get_user ($id) {
		global $sql;
		$or=array();
		$q=$sql->executeSQL("SELECT * FROM `auth` WHERE `id`='$id'");
		$or=array_merge(json_decode($q[0]['user'], true), $q[0]);
		return $or;
	}

	function get_user_id_from_unique_id ($unique_id, $type) {
		global $sql;
		$q=$sql->executeSQL("SELECT `id` FROM `auth` WHERE `user`->'$.type'='".$type."' && `user`->'$.unique_id'='".$unique_id."'");
		return $q[0]['id'];
	}

	function get_all_ids ($type) {
		global $sql;
		return $sql->executeSQL("SELECT `id` FROM `auth` WHERE `user`->'$.type'='$type' ORDER BY `id` DESC");
	}

	function do_login ($post) {
		global $sql, $_SESSION;
		$q=$sql->executeSQL("SELECT `id` FROM `auth` WHERE `user_email`='".$post['user_email']."' && `user_password`='".md5($post['user_password'])."'");
		if ($q[0]['id']) {
			$_SESSION['updated_by']=$q[0]['id'];
			dash::$last_info[]=dash::get_text('5E08BD746B9291577631092', $post['language']);
			dash::$last_redirect='/dash.php';
			return 1;
		}
		else {
			dash::$last_error[]=dash::get_text('5E08BD982AE941577631128', $post['language']);
			return 0;
		}
	}

	function do_logout () {
		global $_SESSION;
		$_SESSION['updated_by']='';
		unset($_SESSION['updated_by']);
		session_destroy();
		session_create_id();
		dash::$last_info[]=dash::get_text('5E09B0F7106051577693431', $post['language']);
		dash::$last_redirect='/';
		return 1;
	}

}
?>