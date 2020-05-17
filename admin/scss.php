<?php
include_once ('../config-init.php');
$sass = new Sass();
$sass->setIncludePath(ABSOLUTE_PATH.'/plugins/bootstrap/scss/');
$sass->setEmbed(true);
$css = $sass->compileFile('wildfire.scss');
echo $css;
?>