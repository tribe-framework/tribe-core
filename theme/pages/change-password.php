<?php
require_once __DIR__ . '/../init.php';
require_once __DIR__ . '/../includes/_header.php';

$auth = new \Wildfire\Auth;
$currentUser = $auth->getCurrentUser();

if (!$currentUser) {
    header('Location: /user/login');
}

if (isset($_POST['password']) && ($_POST['password'] == $_POST['cpassword'])) {
    $dash->push_content_meta($currentUser['id'], 'password', md5($_POST['password']));

    //for admin and crew (staff)
    if ($currentUser['role'] == 'admin' || $currentUser['role'] == 'crew') {
        header('Location: /admin');
    } elseif ($currentUser['role'] == 'member') {
        //for members
        header('Location: /user');
    } else {
        //for visitors and anybody else
        header('Location: /');
    }
}

if (
    ($types['webapp']['user_theme'] ?? false) &&
    file_exists(THEME_PATH . '/pages/user/change-password.php')
):
    include_once THEME_PATH . '/pages/user/change-password.php';
elseif (
    ($types['webapp']['user_theme'] ?? false) &&
    file_exists(THEME_PATH . '/user-change-password.php')
):
    include_once THEME_PATH . '/user-change-password.php';
else:
?>

<form class="form-user" method="post" action="/user/change-password">
    <h2><?= $menus['main']['logo']['name'] ?></h2>

	<h4 class="my-3 font-weight-normal">
        <span class="fas fa-lock mr-2"></span>Change Password
    </h4>

	<?php
        if ($_POST && $_POST['password'] != $_POST['cpassword']) {
            echo '<div class="form-user alert alert-warning">Password mismatch.</div>';
        }
    ?>

    <div class="form-group">
        <label for="inputEmail" class="sr-only">Email address</label>
        <input type="email" name="email" value="<?= $currentUser['email']; ?>" id="inputEmail" class="form-control my-1" placeholder="Email address" required disabled="disabled">
    </div>

    <div class="form-group">
        <label for="inputPassword" class="sr-only">New Password</label>
        <input type="password" name="password" id="inputPassword" class="form-control my-1" placeholder="New password" required>

        <label for="inputPassword" class="sr-only">Confirm Password</label>
        <input type="password" name="cpassword" id="inputPassword" class="form-control my-1" placeholder="Confirm password" required>
    </div>

	<button type="submit" class="btn btn-sm btn-primary btn-block my-1">Change password</button>
	<p class="text-muted small my-5">
        <a href="<?=BASE_URL?>">
            <span class="fas fa-angle-double-left mr-1"></span><?=$menus['main']['logo']['name']?>
        </a>
    </p>
	<p class="text-muted small my-5"
        >&copy; <?= (date('Y') == '2020' ? date('Y') : '2020 - ' . date('Y')); ?> Wildfire
    </p>
</form>

<?php
endif;
require_once __DIR__ . '/../includes/_footer.php';
?>
