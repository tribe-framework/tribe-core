<?php

//session and vars
session_save_path('/tmp');
session_start();
$session_user = $_SESSION['user'] ?? NULL;

// browser debugging
if (defined('ENV') && (ENV == 'dev')) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
}

// wildfire core init
$sql = new Wildfire\Core\MySQL();
$dash = new Wildfire\Core\Dash();

$types=$dash->get_types(ABSOLUTE_PATH.'/config/types.json');
$menus=json_decode(file_get_contents(ABSOLUTE_PATH.'/config/menus.json'), true);

$theme = new Wildfire\Core\Theme();
$admin = new Wildfire\Core\Admin();

isset($types['webapp']['lang'])?:$types['webapp']['lang']='en';

$_GET['ext']=str_replace('vendor/wildfire/auth', 'user', $_GET['ext']);

if (isset($_GET['ext'])) { //for theme
    $ext=explode('/', $_GET['ext']);

    if (count($ext)) {
        $type=$dash->do_unslugify($ext[0]);
    }

    if (count($ext)>1) {
        $slug=$dash->do_unslugify($ext[1]);
    }
} elseif (isset($_GET['type'])) { //for dashboard
    $type=$dash->do_unslugify($_GET['type']);
}

?>