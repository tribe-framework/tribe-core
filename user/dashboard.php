<?php
include_once ('../init.php');
include_once (ABSOLUTE_PATH.'/user/header.php');

if (($types['webapp']['user_theme']??false) && file_exists(THEME_PATH.'/user-dashboard.php')):
	
	include_once (THEME_PATH.'/user-dashboard.php');

endif; ?>

<?php include_once (ABSOLUTE_PATH.'/user/footer.php'); ?>