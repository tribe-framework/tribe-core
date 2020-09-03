<?php
define('THEME', 'prism.wf');

# database credentials
define('DB_NAME', 'prism');
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DB_HOST', 'localhost');

# set ENV to dev for verbose debug of app
define('ENV', '');

# do not modify beyond this line if not needed
define('BARE_URL', $_SERVER['HTTP_HOST']);
define('BASE_URL', 'http://' . BARE_URL);
define('ABSOLUTE_PATH', __DIR__);
define('THEME_URL', BARE_URL . '/themes/' . THEME);
define('THEME_PATH', ABSOLUTE_PATH . '/themes/' . THEME);

/**
 * initiating the autoloader
 */
include_once ABSOLUTE_PATH . '/includes/autoloader.inc.php';
?>
