<?php
require_once __DIR__ . '/../init.php';

$use_custom_theme = $types['webapp']['user_theme'] ?? false;

$form['email'] = trim($_POST['email'] ?? false);
$form['password'] = trim($_POST['password'] ?? false);
$form['c_password'] = trim($_POST['confirm_password'] ?? false);
$form['reset_code'] = trim($_POST['password_reset_code'] ?? false);

$is_password_reset_success = false;
$is_mail_sent = false;
$password_mismatch = false;

// send verification email
if ($form['email'] && !$form['password']) {
    $query = "SELECT id FROM data WHERE type='user' AND content->'$.email'='{$form['email']}' ORDER BY id DESC LIMIT 1";
    $_id = $sql->executeSQL($query)[0]['id'];

    $usr = $dash->getObject($_id);

    require_once __DIR__ . '/../../plugins/sendgrid/core-plugin.php';

    $mailr = array();
    $code = uniqid() . time();
    $dash->push_content_meta($usr['id'], 'password_reset_code', $code);

    $mailr['to_email'] = $form['email'];
    $mailr['to_name'] = '';
    $mailr['subject'] = 'Reset your password for ' . BARE_URL;
    $mailr['body_text'] = $mailr['body_html'] = 'Please reset your password using the following link:<br>' . BASE_URL . "/user/forgot-password?code=$code";

    $mailer->send_email($mailr);

    $is_mail_sent = true;
} else if ($form['password']) { // set new password
	$_id = $sql->executeSQL("SELECT id FROM data WHERE type='user' AND content->'$.password_reset_code'='{$_POST['password_reset_code']}' ORDER BY id DESC LIMIT 1")[0]['id'];

	$usr = $dash->getObject($_id);

	if (($form['password'] != $form['c_password'])) {
		$password_mismatch = true;
	} else {
		$dash->push_content_meta($usr['id'], 'password', md5($_POST['password']));
		$dash->push_content_meta($usr['id'], 'password_reset_code');

		$is_password_reset_success = true;
	}
} else if (isset($_GET['code'])) { // verify reset code
	$_id = $sql->executeSQL("SELECT id FROM data where type='user' AND content->'$.password_reset_code'='{$_GET['code']}' ORDER BY id DESC LIMIT 1")[0]['id'] ?? null;

	if ($_id) {
		$usr = $dash->getObject($_id);
	}

	$is_code_valid = $_id ? true : false;
}

// theme and interface_id
if ($use_custom_theme && file_exists(THEME_PATH.'/pages/user/forgot-password.php')):
	require_once THEME_PATH.'/pages/user/forgot-password.php';
	die();
elseif ($use_custom_theme && file_exists(THEME_PATH.'/user-forgot-password.php')):
	require_once THEME_PATH.'/user-forgot-password.php';
	die();
else:
	require_once __DIR__.'/../includes/_header.php';
?>
	<div class="col-12 col-md-9 col-lg-6 mx-auto">
	<?php if ($is_mail_sent): ?>
		<div class="alert alert-success">
			<span class="d-block mb-2">An email has been sent to you with link to reset password.<br/>Please check your email inbox and spam folder.</span>
			<a href="/">&larr;&nbsp;Go back</a>
		</div>
	<?php elseif ($is_password_reset_success): ?>
		<div class="alert alert-success">Your password has been reset successfully.</div>
		<a href="/user/login" class="btn btn-primary d-block">Login now</a>
	<?php elseif (isset($_GET['code']) && !$is_code_valid): ?>
		<div class="alert alert-danger">Password reset link is invalid</div>
		<a href="/user/forgot-password" class="btn btn-primary d-block">Try Again</a>
	<?php elseif (isset($_GET['code']) || $password_mismatch): // set new password?>
		<form class="form-user" method="post" action="/user/forgot-password">
			<h2><?= $menus['main']['logo']['name'] ?></h2>
			<h4 class="mb-3 font-weight-normal"
				><i class="fas fa-lock mr-2"></i> New Password
			</h4>

			<?php if ($password_mismatch): ?>
				<div class="form-user alert alert-danger">Password mismatch or empty.</div>
			<?php endif ?>

			<label for="inputEmail" class="sr-only">Email address</label>
			<input id="inputEmail" type="email" class="form-control my-1" value="<?=$usr['email']?>" placeholder="Email address" disabled >

			<label for="inputPassword" class="sr-only">Password</label>
			<input id="inputPassword" type="password" name="password" class="form-control my-1" placeholder="Password" required >

			<label for="inputConfirmPassword" class="sr-only">Confirm Password</label>
			<input id="inputConfirmPassword" type="password" name="confirm_password" class="form-control my-1" placeholder="Confirm Password" required >

			<input type="hidden" name="email" value="<?=$usr['email']?>" >
			<input type="hidden" name="password_reset_code" value="<?=$usr['password_reset_code']?>" >

			<button type="submit" class="btn btn-sm btn-primary btn-block my-1"
				>Save new password
			</button>
		</form>
	<?php else: // send verification email ?>
		<form class="form-user" method="post" action="/user/forgot-password">
			<h2><?= $menus['main']['logo']['name'] ?></h2>
			<h4 class="mb-3 font-weight-normal"
				><span class="fas fa-lock mr-2"></span>Forgot Password
			</h4>

			<div class="input-group">
				<label for="inputEmail" class="sr-only">Email address</label>
				<input id="inputEmail" type="email" name="email" class="form-control" placeholder="Email address" required>
			</div>

			<button type="submit" class="btn btn-sm btn-primary btn-block mt-1">Forgot password</button>
		</form>
	<?php endif ?>
	</div>
<?php
	require_once __DIR__.'/../includes/_footer.php';
endif;
?>
