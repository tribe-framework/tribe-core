<?php
$use_custom_theme = $types['webapp']['user_theme'] ?? false;

if ($use_custom_theme && file_exists(THEME_PATH . '/pages/user/_footer.php')):
	include_once THEME_PATH . '/pages/user/_footer.php';
elseif ($use_custom_theme && file_exists(THEME_PATH . '/pages/user/footer.php')):
	include_once THEME_PATH . '/pages/user/footer.php';
elseif ($use_custom_theme && file_exists(THEME_PATH . '/user-footer.php')):
	include_once THEME_PATH . '/user-footer.php';
else:
?>
	<script src="/vendor/wildfire/auth/theme/assets/plugins/jquery.min.js"></script>
	<script src="/vendor/wildfire/auth/theme/assets/plugins/popper/popper.min.js"></script>
	<script src="/vendor/wildfire/auth/theme/assets/plugins/bootstrap/dist/js/bootstrap.min.js"></script>
</body>
</html>
<?php endif;?>
