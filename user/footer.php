<?php
if (($types['webapp']['user_theme']??false) && file_exists(THEME_PATH.'/user-footer.php')):
	include_once (THEME_PATH.'/user-footer.php');
else: ?>

	<script src="/plugins/jquery.min.js"></script>
	<script src="/plugins/popper/popper.min.js"></script>
	<script src="/plugins/moment.js"></script>
	<script src="/plugins/bootstrap/dist/js/bootstrap.min.js"></script>
	<script src="/plugins/typeout/typeout.js"></script>
</body>
</html>

<?php endif; ?>