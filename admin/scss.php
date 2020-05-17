<?php
include_once ('../config-init.php');
$sass = new Sass();
$sass->setStyle(Sass::STYLE_COMPRESSED);
$css = $sass->compileFile(ABSOLUTE_PATH.'/plugins/bootstrap/scss/bootstrap.scss');
echo $css;
?>