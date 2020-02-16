<?php
session_start();
date_default_timezone_set('Asia/Kolkata');
include_once('../config-vars.php');

if (!$_SESSION['language']) $_SESSION['language']='en';

include_once(ABSOLUTE_PATH.'/includes/mysql.class.php');
$sql = new MySQL(DB_NAME, DB_USER, DB_PASS, DB_HOST);

include_once(ABSOLUTE_PATH.'/includes/dash.class.php');
$dash = new dash();

$page_title='Wildfire Template';
$page_description='Basic starting point for wildfire websites.';
$page_url=BASE_URL;

function curl_post ($url, array $post = NULL, array $options = array()) { 
    $defaults = array( 
        CURLOPT_POST => 1, 
        CURLOPT_HEADER => 0, 
        CURLOPT_URL => $url, 
        CURLOPT_FRESH_CONNECT => 1, 
        CURLOPT_RETURNTRANSFER => 1, 
        CURLOPT_FORBID_REUSE => 1, 
        CURLOPT_TIMEOUT => 4,
        CURLOPT_POSTFIELDS => http_build_query($post) 
    ); 

    $ch = curl_init(); 
    curl_setopt_array($ch, ($options + $defaults));

    if( ! $result = curl_exec($ch)) 
    { 
        trigger_error(curl_error($ch)); 
    } 
    curl_close($ch); 
    return $result; 
}

function get_ip () {
    if (!($ip = $_SERVER['HTTP_CLIENT_IP'])) {
		if (!($ip = $_SERVER['HTTP_X_FORWARDED_FOR']))
	    	$ip = $_SERVER['REMOTE_ADDR'];
	}
    return $ip;
}

function get_district_name_from_id ($id) {
	$q=$sql->executeSQL("SELECT `district` FROM `states` WHERE `id`='$id'");
	return $q[0]['district'];
}

function get_state_name_from_id ($id) {
	$q=$sql->executeSQL("SELECT `state` FROM `states` WHERE `id`='$id'");
	return $q[0]['state'];
}

function breadcrumbs ($arr) {
    global $dash;
    $op='<nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="/dash.php" class="text-secondary"><span class="fas fa-tachometer-alt"></span></a></li>';
    foreach ($arr as $or)
    $op.='<li class="breadcrumb-item"><a href="'.$or['link'].'" class="text-secondary">'.ucfirst($dash->get_text($or['lang_code'])).'</a></li>';
    $op.='</ol></nav>';
    return $op;
}
?>