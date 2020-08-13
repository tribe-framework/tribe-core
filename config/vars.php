<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
date_default_timezone_set('Asia/Kolkata');
define('BASE_URL', 'https://localhost');
define('BARE_URL', 'localhost');
define('ABSOLUTE_PATH', '/home/apurv/srv/html/prism.wf');
define('THEME_URL', 'https://localhost/themes/wildfire-2020');
define('THEME_PATH', '/home/apurv/srv/html/prism.wf/themes/wildfire-2020');
define('DB_NAME', 'prism');
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DB_HOST', 'localhost');
?>