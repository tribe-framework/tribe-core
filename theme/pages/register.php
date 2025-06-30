<?php
/**
 * This file provides a plug and play interface for out of the box user registration
 */
require_once __DIR__.'/../init.php';

$error_op = false;
$app_title = $menus['main']['logo']['name'] ?? '';
$redirect_url = isset($_POST['redirect_url']) ? $_POST['redirect_url'] : '';
$form_email = $_POST['email'] ?? false;
$form_mobile = $_POST['mobile'] ?? false;
$form_password = $_POST['password'] ?? false;

$override_default_role = isset($types['user']['default_role']);

if ($currentUser['id'] ?? false) {
    $user = $dash->getObject($currentUser['id']);
    $auth->doAfterLogin($user, $redirect_url);
} elseif (($form_email || $form_mobile)
    && $form_password
    && ($form_password == $_POST['confirm_password'])
) {
    $user_id = $auth->getUserId($_POST);

    if ($user_id) {
        if (!$user_id['ok']) {
            $error_op = true;
        } else {
            $user = $dash->getObject($user_id);
        }
    } else {
        unset($_POST['confirm_password']);

        if ($override_default_role) {
            $_default_role = $types['user']['default_role'];
            $_POST['role'] = $types['user']['roles'][$_default_role]['role'];
            $_POST['role_slug'] = $types['user']['roles'][$_default_role]['slug'];
        }

        $_POST['type'] = 'user';
        $_POST['user_id'] = $_POST['user_id'] ?? $dash->get_unique_user_id();
        $_POST['slug'] = strtolower($_POST['user_id']);

        $user_id = $dash->pushObject($_POST);
        $user = $dash->getObject($user_id);
    }

    if (!$error_op) {
        $auth->doAfterLogin($user, $redirect_url);
    }
} elseif ($_POST) {
    $error_op = true;
}

$use_custom_theme = $types['webapp']['user_theme'] ?? false;

if ($use_custom_theme && file_exists(THEME_PATH.'/pages/user/register.php')) :
    include_once THEME_PATH.'/pages/user/register.php';
elseif ($use_custom_theme && file_exists(THEME_PATH.'/user-register.php')) :
    include_once THEME_PATH.'/user-register.php';
else:
    // if custom user theme pages don't exist
    include_once __DIR__ . '/../includes/_header.php';
    ?>
    <?php if ($error_op) : ?>
        <div class="alert alert-danger"
            >Form not submitted. Please try again.
        </div>
    <?php endif ?>

    <form class="form-user" method="post" action="/user/register">
        <h2><?= $app_title ?></h2>
        <h4 class="my-3 font-weight-normal">
            <span class="fas fa-lock"></span> Register
        </h4>

        <?php
        $type = 'user';
        if (isset($_GET['role'])) {
            $role = $types['user']['roles'][$_GET['role']] ?? false;
        } else {
            $role['slug'] = 'user'; // default user role as unprivileged user
        }

        if ($role['slug']) {
            echo "<input type='hidden' name='role_slug' value='{$role['slug']}'>";
        }
        ?>

        <?php
        include TRIBE_ROOT . '/vendor/wildfire/admin/theme/includes/form/form.php';
        ?>

        <div class="checkbox my-1 small">
            <label>
                <input type="checkbox" class="my-0" value="remember-me">
                I agree with the terms and conditions
            </label>
        </div>

        <button type="submit" class="btn btn-sm btn-primary btn-block my-1"
            >Register
        </button>

        <a href="/user/login" class="btn btn-sm btn-outline-primary btn-block my-1"
            >Sign in
        </a>

        <p class="text-muted small my-2">
            <a href="/user/forgot-password">
                <span class="fas fa-key"></span> Forgot password?
            </a>
        </p>

        <p class="text-muted small my-5">
            <a href="/">
                <span class="fas fa-angle-double-left"></span> <?= $app_title ?>
            </a>
        </p>

        <p class="text-muted small my-5">
            &copy;&nbsp;
            <?php $year = date('Y') ?>
            <?= $year == '2020 ' ? $year : "2020 - $year" ?>&nbsp;
            <?= $app_title ?? 'Wildfire' ?>
        </p>
    </form>

    <?php
    include_once __DIR__.'/../includes/_footer.php';
endif; // custom user theme
?>
