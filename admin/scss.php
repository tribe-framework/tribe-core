<?php
include_once ('../config-init.php');
$sass = new Sass();
$css = $sass->compileFile(ABSOLUTE_PATH.'/plugins/bootstrap/scss/bootstrap.scss');
echo $css;
?>