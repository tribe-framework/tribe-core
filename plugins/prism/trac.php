<?php
session_start();

include_once $_SERVER['DOCUMENT_ROOT'].'/init.php';

$sql = new MySQL();

include_once('trac.class.php');
$trac = new Trac();

unset($_SERVER['SERVER_SIGNATURE']);

$prism_visit_id=$_POST['prism_visit_id'];

if (!$prism_visit_id) {
	$prism_visit_id = $trac->push_visit(array_merge($_SERVER, $_POST));
} elseif ($_POST['action'] == 'click') {
	$trac->push_visit_meta($prism_visit_id, 'click_'.time(), json_encode($_POST));
} elseif ($_POST['unload']) {
	$trac->push_visit_meta($prism_visit_id, 'time_spent', $_POST['time_spent']);
}

header('Content-Type: application/json');
echo json_encode(array('prism_visit_id'=>$prism_visit_id));
?>
