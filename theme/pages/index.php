<?php
require_once __DIR__ . '/../init.php';

if (!$currentUser['id']) {
    ob_start();
    header('Location: /user/login');
    die();
}

if (($types['webapp']['user_theme'] ?? false)) {
    if (file_exists(THEME_PATH . '/pages/user/index.php')) {
        require_once THEME_PATH . '/pages/user/index.php';
    } else if (file_exists(THEME_PATH . '/user-index.php')) {
        require_once THEME_PATH . '/user-index.php';
    } else {
        require_once __DIR__ . '/../includes/_header.php';
        require_once __DIR__ . '/index.php';
        require_once __DIR__ . '/../includes/_footer.php';
    }
} else {
    require_once __DIR__ . '/../includes/_header.php';
    require_once __DIR__ . '/index.php';
    require_once __DIR__ . '/../includes/_footer.php';
}
