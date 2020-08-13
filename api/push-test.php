<?php
$json = file_get_contents('php://input');
$data = json_decode($json);

var_dump($_GET);
var_dump($_POST);

var_dump($json);
var_dump($data);
?>