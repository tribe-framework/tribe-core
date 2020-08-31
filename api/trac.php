<?php
session_start();
include_once('../config/vars.php');
include_once(ABSOLUTE_PATH.'/includes/mysql.class.php');
$sql = new MySQL(DB_NAME, DB_USER, DB_PASS, DB_HOST);
include_once(ABSOLUTE_PATH.'/includes/trac.class.php');
$trac = new trac();

unset($_SERVER['SERVER_SIGNATURE']);

$prism_visit_id=$_POST['prism_visit_id'];

if (!$prism_visit_id) {
	$_SERVER['prism_visit_id']=uniqid().time();
	$prism_visit_id=$trac->push_visit(array_merge($_SERVER, $_POST));
}
else if ($_POST['action']=='click') {
	$trac->push_visit_meta($prism_visit_id, 'click_'.time(), json_encode($_POST));
}
else if ($_POST['unload']) {
	$trac->push_visit_meta($prism_visit_id, 'time_spent', $_POST['time_spent']);
}

header('Content-Type: application/json');
echo json_encode(array('prism_visit_id'=>$prism_visit_id));
?>