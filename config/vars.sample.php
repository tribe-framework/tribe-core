<?php
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
date_default_timezone_set('Asia/Kolkata');
define('BASE_URL', 'https://xyz-domain-var');
define('BARE_URL', 'xyz-domain-var');
define('ABSOLUTE_PATH', '/var/www/html/xyz-domain-var');
define('THEME_URL', 'https://xyz-domain-var/themes/xyz-domain-var');
define('THEME_PATH', '/var/www/html/xyz-domain-var/themes/xyz-domain-var');
define('DB_NAME', 'xyz-db-name-var');
define('DB_USER', 'xyz-db-name-var');
define('DB_PASS', 'xyz-db-pass-var');
define('DB_HOST', 'localhost');
?>