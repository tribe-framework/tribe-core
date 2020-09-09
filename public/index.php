<?php
include_once '../init.php';

$routeDir = ABSOLUTE_PATH . '/routes';
$route = $_SERVER['SCRIPT_URL'];
$routeFull = $routeDir . $route . '.php';
$routeFullIndex = $routeDir . $route . '/index.php';

if (file_exists($routeFull)) {
    // checking if endpoint is valid php file
    include_once $routeFull;
} elseif (file_exists($routeFullIndex)) {
    // checking if the endpoint is directory with index
    include_once $routeFullIndex;
}

// 403 error
if (array_key_exists('REDIRECT_STATUS', $_SERVER) && $_SERVER['REDIRECT_STATUS'] == 403) {
    WildFire\PageError::forbidden();
}

// 404 error
if (!(file_exists($routeFull) || file_exists($routeFullIndex))) {
    WildFire\PageError::notFound();
}
?>
