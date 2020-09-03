<?php
include_once '../init.php';

$routeDir = ABSOLUTE_PATH . '/routes';
$route = $_SERVER['SCRIPT_URL'];
$routeFull = $routeDir . $route . '.php';
$routeFullIndex = $routeDir . $route . 'index.php';

if (file_exists($routeFull)) {
	include_once $routeFull;
} elseif (file_exists($routeFullIndex)) {
	include_once $routeFullIndex;
} else {
	if (file_exists(THEME_PATH.'/404.php')) {
		include_once THEME_PATH.'/404.php';
	}
}
?>
