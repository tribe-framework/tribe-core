<?php
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
date_default_timezone_set('Asia/Kolkata');
define('BASE_URL', 'https://xyz.com');
define('BARE_URL', 'xyz.com');
define('ABSOLUTE_PATH', '/var/www/html/xyz.com');
define('THEME_URL', 'https://xyz.com/themes/xyz.com');
define('THEME_PATH', '/var/www/html/xyz.com/themes/xyz.com');
define('DB_NAME', 'xyz_com');
define('DB_USER', 'xyz_com');
define('DB_PASS', 'xyz_pass');
define('DB_HOST', 'localhost');
?>