<?php
$errorPath = THEME_PATH . '/403.php';

header($_SERVER['SERVER_PROTOCOL'].' 403', true, 403);

if (!file_exists($errorPath)) {
  echo 'Error: 403';
  die();
}

include_once $errorPath;
?>
