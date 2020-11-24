<?php
include_once ('../init.php');
include_once (ABSOLUTE_PATH.'/user/header.php');
?>

<div class="col-12 col-md-9 col-lg-6 mx-auto">

<?php
if ($_POST['email'] && !$_POST['password']) {
	$usr=$dash->get_content($sql->executeSQL("SELECT `id` FROM `data` WHERE `content`->'$.type' = 'user' && `content`->'$.email' = '".trim($_POST['email'])."' ORDER BY `id` DESC LIMIT 1")[0]['id']);

	include_once(ABSOLUTE_PATH.'/plugins/sendgrid/core-plugin.php');
	$mailr=array();
	$code=uniqid().time();
	$dash->push_content_meta($usr['id'], 'password_reset_code', $code);
	$mailr['to_email']=$_POST['email'];
	$mailr['to_name']='';
	$mailr['subject']='Reset your password for '.BARE_URL;
	$mailr['body_text']=$mailr['body_html']='Please reset your password using the following link:<br>'.BASE_URL.'/user/forgot-password?code='.$code;
	$mailer->send_email($mailr);

	echo '<div class="my-5 mx-auto alert alert-success">An email has been sent to you with link to reset password. Please check your email inbox and spam folder.</div>';
}

else if ($_GET['code'] || ($_POST['password_reset_code'] && (!trim($_POST['password']) || !trim($_POST['confirm_password']) || ($_POST['password']!=$_POST['confirm_password'])))) {
	$usr=$dash->get_content($sql->executeSQL("SELECT `id` FROM `data` WHERE `content`->'$.type' = 'user' && `content`->'$.password_reset_code' = '".trim($_POST['password_reset_code']??$_GET['code'])."' ORDER BY `id` DESC LIMIT 1")[0]['id']); ?>

<form class="form-user" method="post" action="/user/forgot-password"><h2><?php echo $menus['main']['logo']['name']; ?></h2>
	<h4 class="my-3 font-weight-normal"><span class="fas fa-lock"></span>&nbsp;New Password</h4>
	<?php if ($_POST && $_POST['password']!=$_POST['confirm_password'])	echo '<div class="form-user alert alert-danger">Password mismatch or empty.</div>'; ?>

	<label for="inputEmail" class="sr-only">Email address</label>
	<input type="email" id="inputEmail" class="form-control my-1" value="<?= $usr['email']; ?>" placeholder="Email address" disabled>

	<label for="inputPassword" class="sr-only">Password</label>
	<input type="password" name="password" id="inputPassword" class="form-control my-1" placeholder="Password" required>

	<label for="inputConfirmPassword" class="sr-only">Confirm Password</label>
	<input type="password" name="confirm_password" id="inputConfirmPassword" class="form-control my-1" placeholder="Confirm Password" required>

	<input type="hidden" name="email" value="<?= $usr['email']; ?>">
	<input type="hidden" name="password_reset_code" value="<?= $usr['password_reset_code']; ?>">
	<button type="submit" class="btn btn-sm btn-primary btn-block my-1">Save new password</button>
</form>

<?php }

else if (trim($_POST['password']) && $_POST['password']==$_POST['confirm_password']) {
	$usr=$dash->get_content($sql->executeSQL("SELECT `id` FROM `data` WHERE `content`->'$.type' = 'user' && `content`->'$.password_reset_code' = '".$_POST['password_reset_code']."' ORDER BY `id` DESC LIMIT 1")[0]['id']);
	$dash->push_content_meta($usr['id'], 'password', md5($_POST['password']));
	$dash->push_content_meta($usr['id'], 'password_reset_code');
	echo '<div class="my-5 alert alert-success">Your password has been reset successfully.</div><div><a href="/user/login" class="btn btn-primary">Login now</a></div>';
}

else if (($types['webapp']['user_theme']??false) && file_exists(THEME_PATH.'/user-forgot-password.php')) {
	include_once (THEME_PATH.'/user-forgot-password.php');
}

else { ?>

<form class="form-user" method="post" action="/user/forgot-password"><h2><?php echo $menus['main']['logo']['name']; ?></h2>
	<h4 class="my-3 font-weight-normal"><span class="fas fa-lock"></span>&nbsp;Forgot Password</h4>
	<label for="inputEmail" class="sr-only">Email address</label>
	<input type="email" name="email" id="inputEmail" class="form-control my-1" placeholder="Email address" required>

	<button type="submit" class="btn btn-sm btn-primary btn-block my-1">Forgot password</button>
</form>

<?php } ?>

</div>

<?php include_once (ABSOLUTE_PATH.'/user/footer.php'); ?>