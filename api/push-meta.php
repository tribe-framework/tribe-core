<?php
header('Content-Type: application/json');
include_once ('../init.php');
if ($_POST['WEBAPP_API_KEY']==WEBAPP_API_KEY) {
	if (!$_POST['content_privacy'])
		$_POST['content_privacy']='public';
	$or=array();
	if ($_POST['id']) {
		$or['id']=$_POST['id'];
		$dash->push_content_meta($_POST['id'], $_POST['meta_key'], $_POST['meta_value']);
	}
	echo json_encode($or);
}
else
	echo 'Not allowed.';
?>