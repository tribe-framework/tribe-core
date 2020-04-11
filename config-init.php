<?php
session_start();
include_once('config/config-vars.php');

if (!$_SESSION['language']) $_SESSION['language']='en';

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

$page_title='Wildfire Template';
$page_description='Basic starting point for wildfire websites.';
$page_url=BASE_URL;
?>