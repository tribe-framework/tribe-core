<?php
header('Content-Type: application/json');
include_once ('../init.php');
if ($_GET['WEBAPP_API_KEY']) {
	if (!$_GET['content_privacy'])
		$_GET['content_privacy']='public';
	$or=array();
	$or['id']=$dash->push_content($_GET);
	echo json_encode($or);
}
else
	echo json_encode('error'=>'Not allowed.');
?>