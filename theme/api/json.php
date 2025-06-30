<?php
include_once __DIR__ . '/init.php';
header('Content-Type: application/json');
$or = array();

if ($_SESSION['CAPTCHA_STRING'] == $_POST['captcha_input'] && filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL)) {
	$or['successmsg'] = '';
//$uqid=$dash->get_unique_user_id();
	//$dash->push_content(array('type'=>'user', 'email'=>trim($_POST['email']), 'role_slug'=>'subscriber', 'user_id'=>$uqid, 'slug'=>$uqid));
} else {
	$or['errormsg'] = '';
}

echo json_encode($or);
