<?php
session_start();
date_default_timezone_set('Asia/Kolkata');
include_once('config-vars.php');

if (!$_SESSION['language']) $_SESSION['language']='en';

include_once(ABSOLUTE_PATH.'/includes/mysql.class.php');
$sql = new MySQL(DB_NAME, DB_USER, DB_PASS, DB_HOST);

include_once(ABSOLUTE_PATH.'/includes/auth.class.php');
$auth = new auth();

include_once(ABSOLUTE_PATH.'/includes/dash.class.php');
$dash = new dash();

include_once(ABSOLUTE_PATH.'/includes/google.class.php');
$google = new google();

$page_title='Wildfire Template';
$page_description='Basic starting point for wildfire websites.';
$page_url=BASE_URL;
?>