<?php
$json = file_get_contents('php://input');
$data = json_decode($json);

header('Content-Type: application/json');
include_once ('../init.php');

// if the client isn't authorised for server, end the connection
if ($data->WEBAPP_API_KEY == WEBAPP_API_KEY) {
	http_response_code(401);
	die(json_encode(array('error'=>'Not allowed.')));
}

if (!$data->content_privacy) {
	$data->content_privacy='public';
}

$or=array();

if ($data->id) {
	$or['id']=$data->id;
	$dash->push_content_meta($data->id, $data->meta_key, $data->meta_value);
}

echo json_encode($or);
?>