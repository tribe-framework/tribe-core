<?php
header('Content-Type: application/json');
include_once ('../init.php');
unset($_SERVER['SERVER_SIGNATURE']);
echo json_encode($_SERVER);
?>