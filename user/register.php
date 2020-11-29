<?php
include_once '../init.php';

$error_op = null;

$nodeEnv = $_GET['env'] == 'dev' || null;
$formEmail = $_POST['email'] ?? null;
$formPhone = $_POST['mobile'] ?? null;
$formPassword = $_POST['password'] ?? null;
$formConfirmPass = $_POST['confirm_password'] ?? null;

if ($_SESSION['user']['id']) { // if user's session already exists
	$user=$dash->get_content($_SESSION['user']['id']);
	$dash->after_login($user['role_slug'], ($_POST['redirect_url'] ?? ''));
} elseif (
	($formEmail || $formPhone) &&
	$formPassword &&
	($formPassword == $formConfirmPass)
) {
	$q = null;
	if ($formEmail) {
		$q = $sql->executeSQL("
			SELECT `id`
			FROM `data`
			WHERE `content`->'$.email'='".$formEmail."' &&
			`content`->'$.password'='".md5($formPassword)."' &&
			`content`->'$.type'='user'
		");
	} elseif ($formPhone) {
		$q = $sql->executeSQL("
			SELECT `id`
			FROM `data`
			WHERE `content`->'$.mobile'='".$formPhone."' &&
			`content`->'$.password'='".md5($formPassword)."' &&
			`content`->'$.type'='user'
		");
	}

	if ($q[0]['id']) {
		$user = $dash->get_content($q[0]['id']);
	} else {
		$user_id = $dash->push_content($_POST);
		$user = $dash->get_content($user_id);
	}
	$dash->after_login($user['role_slug'], ($_POST['redirect_url'] ?? ''));
} elseif ($_POST) {
	$error_op='<div class="alert alert-danger">Form not submitted. Please try again.</div>';
}

include_once ABSOLUTE_PATH.'/user/header.php';

if (
	($types['webapp']['user_theme'] ?? false) &&
	file_exists(THEME_PATH.'/user-register.php')
):
	include_once (THEME_PATH.'/user-register.php');
else:
?>

<?= $error_op ?? '' ?>

<form class="form-user" method="post" action="/user/register">
	<h2><?= $menus['main']['logo']['name'] ?></h2>
	<h4 class="my-3 font-weight-normal">
		<span class="fas fa-lock"></span>&nbsp;Register
	</h4>

	<?php
		$type = 'user';
		$role = $types['user']['roles'][$_GET[role]];
		if ($role['slug']):
	?>
		<input type="hidden" name="role_slug" value="<?= $role['slug']?>">
	<?php
		endif;
		include '../admin/form.php';
	?>

	<div class="checkbox my-1 small">
		<label>
			<input type="checkbox" class="my-0" value="remember-me">
			I agree with the terms and conditions
		</label>
	</div>

	<button type="submit" class="btn btn-sm btn-primary btn-block my-1">
		Register
	</button>
	<a
		class="btn btn-sm btn-outline-primary btn-block my-1"
		href="/user/login<?= $nodeEnv ? '?env=dev' : null ?>">
		Sign in
	</a>
	<p class="text-muted small my-2">
		<a href="/user/forgot-password<?= $nodeEnv ? '?env=dev' : null ?>">
			<span class="fas fa-key"></span>&nbsp;Forgot password?
		</a>
	</p>
	<p class="text-muted small my-5">
		<a href="<?= BASE_URL ?>">
			<span class="fas fa-angle-double-left"></span>&nbsp;
			<?= $menus['main']['logo']['name'] ?>
		</a>
	</p>
	<p class="text-muted small my-5">
		&copy; <?= (date('Y')=='2020'?date('Y'):'2020 - '.date('Y')) ?>
		Wildfire
	</p>
</form>

<?php
endif;
include_once ABSOLUTE_PATH.'/user/footer.php';
?>
