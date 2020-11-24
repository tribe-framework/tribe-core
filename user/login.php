<?php
include_once ('../init.php');
if ($_GET['action']=='exit') {session_destroy(); ob_start(); header('Location: '.BASE_URL.'/user/login');}

if (($_POST['email'] || $_POST['mobile']) && $_POST['password']) {
	if ($_POST['email'])
		$q=$sql->executeSQL("SELECT `id` FROM `data` WHERE `content`->'$.email'='".$_POST['email']."' && `content`->'$.password'='".md5($_POST['password'])."' && `content`->'$.type'='user'");
	else if ($_POST['mobile'])
		$q=$sql->executeSQL("SELECT `id` FROM `data` WHERE `content`->'$.mobile'='".$_POST['mobile']."' && `content`->'$.password'='".md5($_POST['password'])."' && `content`->'$.type'='user'");
	if ($q[0]['id']) {
		$user=$dash->get_content($q[0]['id']);
		$dash->after_login($user['role_slug'], (isset($_POST['redirect_url'])?$_POST['redirect_url']:''));
	}
}
else if ($_SESSION['user']['id']) {
	$user=$dash->get_content($_SESSION['user']['id']);
	$dash->after_login($user['role_slug'], (isset($_POST['redirect_url'])?$_POST['redirect_url']:''));
}

include_once (ABSOLUTE_PATH.'/user/header.php');

if (($types['webapp']['user_theme']??false) && file_exists(THEME_PATH.'/user-login.php')):
	include_once (THEME_PATH.'/user-login.php');
else: ?>

<form class="form-user" method="post" action="/user/login"><h2><?php echo $menus['main']['logo']['name']; ?></h2>
	<h4 class="my-3 font-weight-normal"><span class="fas fa-lock"></span>&nbsp;Sign in</h4>
	<?= ($_POST?'<div class="alert alert-danger">Wrong credentials. Please try again.</div>':''); ?>
	<label for="inputEmail" class="sr-only">Email address</label>
	<input type="email" name="email" id="inputEmail" class="form-control my-1" value="<?= $_POST['email']; ?>" placeholder="Email address" required autofocus>
	<label for="inputPassword" class="sr-only">Password</label>
	<input type="password" name="password" id="inputPassword" class="form-control my-1" placeholder="Password" required>
	<div class="checkbox my-1 small"><label><input type="checkbox" class="my-0" value="remember-me"> Remember me</label></div>
	<button type="submit" class="btn btn-sm btn-primary btn-block my-1">Sign in</button>
	<a class="btn btn-sm btn-outline-primary btn-block my-1" href="/user/register">Register</a>
	<p class="text-muted small my-2"><a href="/user/forgot-password"><span class="fas fa-key"></span>&nbsp;Forgot password?</a></p>
	<p class="text-muted small my-5"><?php echo '<a href="'.BASE_URL.'"><span class="fas fa-angle-double-left"></span>&nbsp;'.$menus['main']['logo']['name'].'</a>'; ?></p>
	<p class="text-muted small my-5">&copy; <?php echo (date('Y')=='2020'?date('Y'):'2020 - '.date('Y')); ?> Wildfire</p>
</form>

<?php endif; ?>

<?php include_once (ABSOLUTE_PATH.'/user/footer.php'); ?>