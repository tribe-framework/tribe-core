<?php
session_start();
include_once('config/config-vars.php');
$types=json_decode(file_get_contents(THEME_PATH.'/config/types.json'), true);
$menus=json_decode(file_get_contents(THEME_PATH.'/config/menus.json'), true);

foreach ($types as $key=>$type) {
	if ($type['type']=='content') {
		$publishing_options_json='{
	        "input_slug": "publishing_option",
	        "input_placeholder": "Publishing option",
	        "input_type": "select",
	        "input_options": [
	          "Public story",
	          "Private link",
	          "Draft"
	        ],
	        "list_field": true,
	        "input_unique": false
	      }';
		$types[$key]['modules'][]=json_decode($publishing_options_json, true);
  }
}

isset($types['webapp']['lang'])?:$types['webapp']['lang']='en';

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

include_once(ABSOLUTE_PATH.'/admin/functions.php');
include_once(THEME_PATH.'/functions.php');

if (isset($_GET['ext'])) {
	$ext=explode('/', $_GET['ext']);
	if (count($ext))
		$type=$ext[0];
	if (count($ext)>1)
		$slug=$ext[1];
}
else if (isset($_GET['type'])) {
	$type=$_GET['type'];
}

?>