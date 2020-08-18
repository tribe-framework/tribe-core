<?php
$json = file_get_contents('php://input');
$data = json_decode($json);

header('Content-Type: application/json');
include_once ('../init.php');

if ($data->WEBAPP_API_KEY) {
	if (!$data->content_privacy)
		$data->content_privacy='public';
	$or=array();
	$or['id']=$dash->push_content((array) $data);
	$or['PHPSESSIONID'] = session_id();
	echo json_encode($or);
}
else {
	echo json_encode(array('error'=>'Not allowed.'));
}