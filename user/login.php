<?php
include_once '../init.php';

if ($_GET['action']=='exit') {
	session_destroy();
	ob_start();
	$r_url = trim(BASE_URL).($_GET['env'] == 'dev' ? '?env=dev' : null);

	header('Location: '.$r_url);
	die();
}

$formEmail = $_POST['email'] ?? null;
$formPhone = $_POST['mobile'] ?? null;
$formPassword = $_POST['password'] ?? null;

$user = null;

if (($formEmail || $formPhone) && $formPassword) {
	$q = null;
	if ($formEmail) {
		$q=$sql->executeSQL("
			SELECT `id`
			FROM `data`
			WHERE `content`->'$.email'='".$formEmail."' &&
				`content`->'$.password'='".md5($formPassword)."' &&
				`content`->'$.type'='user'
		");
	} elseif ($formPhone) {
		$q=$sql->executeSQL("
			SELECT `id`
			FROM `data`
			WHERE `content`->'$.mobile'='".$formPhone."' &&
			`content`->'$.password'='".md5($formPassword)."' &&
			`content`->'$.type'='user'
		");
	}
	if ($q[0]['id']) {
		$user = $dash->get_content($q[0]['id']);
	}
} elseif ($_SESSION['user']['id']) { // if user is already logged in
	$user = $dash->get_content($_SESSION['user']['id']);
}

if ($user) {
	$dash->after_login($user['role_slug'], ($_POST['redirect_url'] ?? ''));
}

include_once ABSOLUTE_PATH.'/user/header.php';

if (
	($types['webapp']['user_theme']??false) &&
	file_exists(THEME_PATH.'/user-login.php')
):
	include_once THEME_PATH.'/user-login.php';
else:
?>
	<div class="container">
		<div class="row">
			<div class="col-md-10">
				<form class="form-user" method="post" action="/user/login">
					<h2><?= $menus['main']['logo']['name'] ?></h2>
					<h4 class="my-3 font-weight-normal">
						<span class="fas fa-lock"></span>&nbsp;Sign in
					</h4>
					<label for="inputEmail" class="sr-only">Email address</label>
					<input
						type="email"
						name="email"
						id="inputEmail"
						class="form-control my-1"
						placeholder="Email address"
						required
						autofocus
					>

					<label for="inputPassword" class="sr-only">Password</label>
					<input
						type="password"
						name="password"
						id="inputPassword"
						class="form-control my-1"
						placeholder="Password"
						required
					>
					<div class="checkbox my-1 small">
						<label>
							<input type="checkbox" class="my-0" value="remember-me">Remember me
						</label>
					</div>

					<button type="submit" class="btn btn-sm btn-primary btn-block my-1">
						Sign in
					</button>

					<a
						class="btn btn-sm btn-outline-primary btn-block my-1"
						href="/user/register"
					>
						Register
					</a>
					<p class="text-muted small my-2">
						<a href="/user/forgot-password">
							<span class="fas fa-key"></span>&nbsp;Forgot password?
						</a>
					</p>
					<p class="text-muted small my-5">
						<a href="<?= BASE_URL ?>">
							<span class="fas fa-angle-double-left"></span>&nbsp;
							<?= $menus['main']['logo']['name'] ?>
						</a>
					</p>
					<p class="text-muted small my-5">&copy;
						<?= (date('Y')=='2020'?date('Y'):'2020 - '.date('Y')); ?>
						Wildfire
					</p>
				</form>
			</div>
		</div>
	</div>
<?php
endif;
include_once ABSOLUTE_PATH.'/user/footer.php'
?>
