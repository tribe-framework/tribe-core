<?php
$json = file_get_contents('php://input');
$data = json_decode($json);

header('Content-Type: application/json');
include_once ('../init.php');

// if the client isn't authorised for server, end the connection
if ($data->WEBAPP_API_KEY != WEBAPP_API_KEY) {
	die(json_encode(array('error'=>'Not allowed.')));
}

if (!$data->content_privacy) {
	$data->content_privacy='public';
}

// reverse ip api
$url = 'http://ip-api.com/json';

if ($_SERVER['HTTP_HOST'] != 'localhost') {
	$url = $url.'/'.$_SERVER['HTTP_HOST'];
}

// get reverse ip
$geoData = json_decode($dash->do_shell_command('curl -X GET '.$url), true);

$geo = new StdClass;
$geo->city = $geoData['city'];
$geo->country = $geoData['country'];

$data->geo = $geo;
// end of reverse-ip

$or=array();
$or['id']=$dash->push_content((array) $data);
$or['PHPSESSIONID'] = session_id();

echo json_encode($or);
?>