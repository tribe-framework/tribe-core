<?php
include_once __DIR__ . '/../init.php';

if (!$currentUser['id']) {
    ob_start();
    header('Location: /user/login');
}

if (
    ($types['webapp']['user_theme'] ?? false) &&
    file_exists(THEME_PATH . "/pages/user/$slug.php")
):
    include_once THEME_PATH . "/pages/user/$slug.php";
elseif (
    ($types['webapp']['user_theme'] ?? false) &&
    file_exists(THEME_PATH . '/pages/user/single.php')
):
    include_once THEME_PATH . '/pages/user/single.php';
elseif (
    ($types['webapp']['user_theme'] ?? false) &&
    file_exists(THEME_PATH . '/user-single.php')
):
    include_once THEME_PATH . '/user-single.php';
else:
    include_once THEME_PATH . '/pages/404.php';
endif;
