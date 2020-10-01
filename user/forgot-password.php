<?php
include_once ('../init.php');
include_once (ABSOLUTE_PATH.'/user/header.php');

if ($_POST['email']) {
	print_r($sql->executeSQL("SELECT `id` FROM `data` WHERE `content`->'$.type' = 'user' && `content`->'$.email' = '".trim($_POST['email'])."' ORDER BY `id` DESC LIMIT 1")[0]['id']);
}

if (($types['webapp']['user_theme']??false) && file_exists(THEME_PATH.'/user-forgot-password.php')):
	include_once (THEME_PATH.'/user-forgot-password.php');
else: ?>

<form class="form-user" method="post" action="/user/forgot-password"><h2><?php echo $menus['main']['logo']['name']; ?></h2>
	<h4 class="my-3 font-weight-normal"><span class="fas fa-lock"></span>&nbsp;Forgot Password</h4>
	<?php if ($_POST && $_POST['password']!=$_POST['cpassword'])	echo '<div class="form-user alert alert-warning">Password mismatch.</div>'; ?>
	<label for="inputEmail" class="sr-only">Email address</label>
	<input type="email" name="email" id="inputEmail" class="form-control my-1" placeholder="Email address" required>

	<button type="submit" class="btn btn-sm btn-primary btn-block my-1">Forgot password</button>
	<p class="text-muted small my-5"><?php echo '<a href="'.BASE_URL.'"><span class="fas fa-angle-double-left"></span>&nbsp;'.$menus['main']['logo']['name'].'</a>'; ?></p>
	<p class="text-muted small my-5">&copy; <?php echo (date('Y')=='2020'?date('Y'):'2020 - '.date('Y')).' '.BARE_URL; ?></p>
</form>

<?php endif; ?>

<?php include_once (ABSOLUTE_PATH.'/user/footer.php'); ?>