<?php
$json = file_get_contents('php://input');
$data = json_decode($json);

header('Content-Type: application/json');
include_once ('../init.php');

if ($data->WEBAPP_API_KEY) {
  echo json_encode(array('ok'=>'true'));
} else {
  echo json_encode(array('ok'=>'false'));
}
?>