<?php
$errorPath = THEME_PATH . '/404.php';

if (file_exists($errorPath)) {
  header($_SERVER['SERVER_PROTOCOL'].' 404', true, 404);
  include_once $errorPath;
}
?>
