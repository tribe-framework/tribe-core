<?php
include_once ('../config-init.php');
$sass = new Sass();
$css = $sass->compileFile(ABSOLUTE_PATH.'/admin/css/wildfire.scss');
echo $css;
?>