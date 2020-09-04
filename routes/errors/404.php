<?php
$errorPath = THEME_PATH . '/404.php';

if (file_exists($errorPath)) {
  include_once $errorPath;
}
?>
