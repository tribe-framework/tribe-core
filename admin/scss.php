<?php
include_once ('../config-init.php');
$sass = new Sass();
$sass->setEmbed(true);
$css = $sass->compileFile(ABSOLUTE_PATH.'/plugins/bootstrap/scss/bootstrap.scss');
echo $css;
?>