<?php
include_once ('../config-init.php');
$sass = new Sass();
$sass->setEmbed(true);
$css = $sass->compileFile(ABSOLUTE_PATH.'/plugins/bootstrap/wildfire.scss');
echo $css;
?>