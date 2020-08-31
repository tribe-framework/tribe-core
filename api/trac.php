<?php
header('Content-Type: application/json');
include_once ('../init.php');
echo json_encode($_POST);
?>