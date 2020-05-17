<?php
include_once ('../config-init.php');
$sass = new Sass();
$css = $sass->compileFile(BASE_PATH.'/admin/css/wildfire.scss');
echo $css;
?>