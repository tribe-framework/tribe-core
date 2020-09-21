<?php
include_once ('../init.php');
include_once (ABSOLUTE_PATH.'/user/header.php');

if (!$_SESSION['user']['id']) {header ('Location: /user/login'); die();}

if (($types['webapp']['user_theme']??false) && file_exists(THEME_PATH.'/user-'.$slug.'.php')):
	include_once (THEME_PATH.'/user-'.$slug.'.php');
else:
	include_once (THEME_PATH.'/404.php');
endif; ?>

<?php include_once (ABSOLUTE_PATH.'/user/footer.php'); ?>