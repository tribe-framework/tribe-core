<?php
header('Content-Type: application/json');
include_once ('../init.php');
if ($_POST['WEBAPP_API_KEY']==WEBAPP_API_KEY) {
	if (!$_POST['content_privacy'])
		$_POST['content_privacy']='public';
	$or=array();
	$or['id']=$dash->push_content($_POST);
	echo json_encode($or);
}
else
	echo 'Not allowed.';
?>