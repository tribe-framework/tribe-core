<?php
require_once 'vars.php';

# do not modify beyond this line if not needed
define('BARE_URL', $_SERVER['HTTP_HOST']);
define('BASE_URL', 'http://' . BARE_URL);
define('ABSOLUTE_PATH', __DIR__);
define('THEME_URL', BARE_URL . '/themes/' . THEME);
define('THEME_PATH', ABSOLUTE_PATH . '/themes/' . THEME);

session_save_path('/tmp');
?>
