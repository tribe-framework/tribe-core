<?php
require_once 'vars.php';
require_once __DIR__.'/vendor/autoload.php';

# do not modify beyond this line if not needed
define('BARE_URL', $_SERVER['HTTP_HOST']);
define('BASE_URL', 'http://' . BARE_URL);
define('ABSOLUTE_PATH', __DIR__);
define('THEME_URL', BARE_URL . '/themes/' . THEME);
define('THEME_PATH', ABSOLUTE_PATH . '/themes/' . THEME);

session_save_path('/tmp');

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
?>
