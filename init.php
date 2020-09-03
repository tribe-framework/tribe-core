<?php
session_save_path('/tmp');
session_start();

include_once 'config.php';

if (file_exists(THEME_PATH.'/config/vars.php')) {
    include_once(THEME_PATH.'/config/vars.php');
}

// browser debugging
if (ENV == 'dev') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
	error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
}

$session_user = $_SESSION['user'] ?? NULL;

include_once 'routes/admin/functions.php';

$sql = new MySQL();

$userless_install=0;
$q=$sql->executeSQL("SELECT `id` FROM `data` WHERE `content`->'$.type'='user'");

if (!$q[0]['id']) {
    $userless_install=1;
}

$dash = new dash();
$theme = new theme();

include_once(THEME_PATH.'/functions.php');

$types=$dash->get_types(THEME_PATH.'/config/types.json');
$menus=json_decode(file_get_contents(THEME_PATH.'/config/menus.json'), true);
$admin_menus=json_decode(file_get_contents(ABSOLUTE_PATH.'/routes/admin/config/admin_menus.json'), true);

isset($types['webapp']['lang'])?:$types['webapp']['lang']='en';

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
