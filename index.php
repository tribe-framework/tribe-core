<?php
include_once ('config-init.php');
$ext=explode('/', $_GET['ext']);
if (!$ext[0])	include_once (THEME_PATH.'/index.php');
elseif (($type=$ext[0]) && ($slug=$ext[1])) {include_once (THEME_PATH.'/single.php');}
elseif ($type) {include_once (THEME_PATH.'/archive.php');}
else {include_once (THEME_PATH.'/404.php');}