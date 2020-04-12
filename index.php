<?php
include_once ('config-init.php');

if ($type && $slug)
	include_once (THEME_PATH.'/single.php');
elseif ($type && !$slug)
	include_once (THEME_PATH.'/archive.php');
else
	include_once (THEME_PATH.'/index.php');
?>