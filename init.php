<?php
session_save_path('/tmp');
session_start();

include_once __DIR__ . '/config/vars.php';
require __DIR__ . '/vendor/autoload.php';

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

$session_user = $_SESSION['user'] ?? NULL;

$sql = new Wildfire\Core\MySQL();

$userless_install=0;
$q=$sql->executeSQL("SELECT `id` FROM `data` WHERE `content`->'$.type'='user'");

if (!$q[0]['id']) {
    $userless_install=1;
}

$dash = new Wildfire\Core\Dash();
$theme = new Wildfire\Core\Theme();
$admin = new Wildfire\Core\Admin();

$types=$dash->get_types(ABSOLUTE_PATH.'/config/types.json');
$menus=json_decode(file_get_contents(ABSOLUTE_PATH.'/config/menus.json'), true);

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
