<?php
header('Content-Type: application/json');
include_once ('../init.php');
if ($_GET['WEBAPP_API_KEY']) {
	if (!$_GET['content_privacy'])
		$_GET['content_privacy']='public';
	$or=array();
	if ($_GET['id']) {
		$or['id']=$_GET['id'];
		$dash->push_content_meta($_GET['id'], $_GET['meta_key'], $_GET['meta_value']);
	}
	echo json_encode($or);
}
else
	echo json_encode('error'=>'Not allowed.');
?>