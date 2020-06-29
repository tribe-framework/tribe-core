<?php
session_start();
include_once('config/config-vars.php');

include_once(ABSOLUTE_PATH.'/admin/functions.php');

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

include_once(THEME_PATH.'/functions.php');

$types=$dash::get_types(THEME_PATH.'/config/types.json');
$menus=json_decode(file_get_contents(THEME_PATH.'/config/menus.json'), true);
$admin_menus=json_decode(file_get_contents(ABSOLUTE_PATH.'/admin/config/admin_menus.json'), true);

$userless_install=0;
$q=$sql->executeSQL("SELECT `id` FROM `data` WHERE `content`->'$.type'='user'");
if (!$q[0]['id'])
	$userless_install=1;

isset($types['webapp']['lang'])?:$types['webapp']['lang']='en';

if (isset($_GET['ext'])) { //for theme
	$ext=explode('/', $_GET['ext']);
	if (count($ext))
		$type=$dash::do_unslugify($ext[0]);
	if (count($ext)>1)
		$slug=$dash::do_unslugify($ext[1]);
}
else if (isset($_GET['type'])) { //for dashboard
	$type=$dash::do_unslugify($_GET['type']);
}
var_dump($types);
?>