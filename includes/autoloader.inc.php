<?php
/**
 * This autoloader helps in loading classes
 */

spl_autoload_register(function ($class) {
  $path = '/classes/';
  $extension = '.class.php';
  $fullpath = ABSOLUTE_PATH . $path . $class . $extension;

  if (!file_exists($fullpath)) {
    return false;
  }

  include_once $fullpath;
});
?>
