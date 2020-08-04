<?php
if (($types['webapp']['dashboard_theme']??false) && file_exists(THEME_PATH.'/footer.php')):
	include_once (THEME_PATH.'/footer.php');
else: ?>
</body>
</html>
<?php endif; ?>