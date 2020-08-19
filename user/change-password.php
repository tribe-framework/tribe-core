<?php
include_once ('../init.php');
include_once (ABSOLUTE_PATH.'/user/header.php');

if ($_POST['password'] && ($_POST['password']==$_POST['cpassword'])) {
	$dash->push_content_meta($session_user['id'], 'password', md5($_POST['password']));

	//for admin and crew (staff)
	if ($session_user['role']=='admin' || $session_user['role']=='crew')
		header('Location: /admin');

	//for members
	else if ($session_user['role']=='member')
		header('Location: /user');

	//for visitors and anybody else
	else 
		header('Location: /');
}

if (($types['webapp']['user_theme']??false) && file_exists(THEME_PATH.'/user-change-password.php')):
	include_once (THEME_PATH.'/user-change-password.php');
else: ?>

<form class="form-user" method="post" action="/user/change-password"><h2><?php echo $menus['main']['logo']['name']; ?></h2>
	<h4 class="my-3 font-weight-normal"><span class="fas fa-lock"></span>&nbsp;Change Password</h4>
	<?php if ($_POST && $_POST['password']!=$_POST['cpassword'])	echo '<div class="form-user alert alert-warning">Password mismatch.</div>'; ?>
	<label for="inputEmail" class="sr-only">Email address</label>
	<input type="email" name="email" value="<?php echo $session_user['email']; ?>" id="inputEmail" class="form-control my-1" placeholder="Email address" required disabled="disabled">
	<label for="inputPassword" class="sr-only">New Password</label>
	<input type="password" name="password" id="inputPassword" class="form-control my-1" placeholder="New password" required>
	<label for="inputPassword" class="sr-only">Confirm Password</label>
	<input type="password" name="cpassword" id="inputPassword" class="form-control my-1" placeholder="Confirm password" required>

	<button type="submit" class="btn btn-sm btn-primary btn-block my-1">Change password</button>
	<p class="text-muted small my-5"><?php echo '<a href="'.BASE_URL.'"><span class="fas fa-angle-double-left"></span>&nbsp;'.$menus['main']['logo']['name'].'</a>'; ?></p>
	<p class="text-muted small my-5">&copy; <?php echo (date('Y')=='2020'?date('Y'):'2020 - '.date('Y')); ?> Wildfire</p>
</form>

<?php endif; ?>

<?php include_once (ABSOLUTE_PATH.'/user/footer.php'); ?>