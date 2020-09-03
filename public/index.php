<?php
include_once '../init.php';

$routeDir = ABSOLUTE_PATH . '/routes';
$route = $_SERVER['SCRIPT_URL'];
$routeFull = $routeDir . $route . '.php';
$routeFullIndex = $routeDir . $route . '/index.php';

if (file_exists($routeFull)) {
    include_once $routeFull;
} elseif (file_exists($routeFullIndex)) {
    include_once $routeFullIndex;
} else {
    $e = new PageError();
    $e->pageNotFound();
}
?>
