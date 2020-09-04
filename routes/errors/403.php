<?php
$errorPath = THEME_PATH . '/403.php';

if (!file_exists($errorPath)) {
  echo 'Error: 403';
  die();
}

include_once $errorPath;
?>
