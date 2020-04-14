<?php
session_start();
include_once('config/config-vars.php');

isset($_SESSION['language'])?:$_SESSION['language']='en';

$types=json_decode(file_get_contents(THEME_PATH.'/config/types.json'), true);
$menus=json_decode(file_get_contents(THEME_PATH.'/config/menus.json'), true);

include_once(ABSOLUTE_PATH.'/includes/mysql.class.php');
$sql = new MySQL(DB_NAME, DB_USER, DB_PASS, DB_HOST);

include_once(ABSOLUTE_PATH.'/includes/auth.class.php');
$auth = new auth();

include_once(ABSOLUTE_PATH.'/includes/dash.class.php');
$dash = new dash();

include_once(ABSOLUTE_PATH.'/includes/theme.class.php');
$theme = new theme();

include_once(ABSOLUTE_PATH.'/includes/google.class.php');
$google = new google();

include_once(ABSOLUTE_PATH.'/includes/blueimp.class.php');

if (isset($_GET['ext'])) {
	$ext=explode('/', $_GET['ext']);
	$type=$ext[0];
	$slug=$ext[1];
	$typedata=$types[$type];
	$postdata=$dash::get_content(array('type'=>$type, 'slug'=>$slug));
	$postdata_modified=$postdata;

	$headmeta_title=$types[$type]['headmeta_title'];
	$headmeta_description=$types[$type]['headmeta_description'];

	$append_phrase='';
	if ($types[$type]['headmeta_title_append']) {
		foreach ($types[$type]['headmeta_title_append'] as $appendit) {
			$key=$appendit['type']; $value=$appendit['slug'];
			$append_phrase.=' '.$types[$type]['headmeta_title_glue'].' '.$types[$key][$value];
		}
	}
	$prepend_phrase='';
	if ($types[$type]['headmeta_title_prepend']) {
		foreach ($types[$type]['headmeta_title_prepend'] as $prependit) {
			$key=$prependit['type']; $value=$prependit['slug'];
			$prepend_phrase.=$types[$key][$value].' '.$types[$type]['headmeta_title_glue'].' ';
		}
	}
	$postdata_modified[$headmeta_title]=$prepend_phrase.$postdata[$headmeta_title].$append_phrase;
}
else if (isset($_GET['type'])) {
	$type=$_GET['type'];
}

include_once(ABSOLUTE_PATH.'/admin/functions.php');
include_once(THEME_PATH.'/functions.php');
?>