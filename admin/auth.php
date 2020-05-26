<?php include_once ('../config-init.php'); ?>
<html lang="<?php echo $types['webapp']['lang']; ?>">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<title><?php echo (isset($headmeta_title)?$headmeta_title.' &raquo; ':'').'Wildfire Entity'; ?></title>
	<meta name="description" content="Access authorisation<?php echo (isset($headmeta_title)?' for '.$headmeta_title:''); ?>">
	<link rel="stylesheet" href="https://use.typekit.net/xkh7dxd.css">
	<link href="<?php echo BASE_URL; ?>/admin/css/bootstrap.min.css" rel="stylesheet">
	<link href="<?php echo BASE_URL; ?>/admin/css/wildfire.css" rel="stylesheet">
	<link href="/plugins/fontawesome/css/all.min.css" rel="stylesheet">
	<link href="/plugins/datatables/datatables.min.css" rel="stylesheet">
	<link href="<?php echo BASE_URL; ?>/admin/css/custom.css" rel="stylesheet">
  <link href="<?php echo BASE_URL; ?>/admin/css/auth.css" rel="stylesheet">
</head>

<body class="text-center">
  <hr class="hr fixed-top" style="margin:0 !important;">

  <?php if ($_GET['section']=='exit') {
    session_destroy();
  } ?>

  <?php if ($_GET['action']=='signin' && $_POST['email'] && $_POST['password']) {
    $q=$sql->executeSQL("SELECT `id` FROM `data` WHERE `content`->'$.email'='".$_POST['email']."' && `content`->'$.password'='".md5($_POST['password'])."' && `content`->'$.type'='user'");
    if ($q[0]['id']) {
      $user=$dash->get_content($q[0]['id']);
      $_SESSION['user_id']=$user['user_id'];
      $_SESSION['email']=$user['email'];
      $_SESSION['user']=$user;
      header('Location: /admin');
    }
  } ?>

<?php if ($_GET['section']=='register'): ?>
  <form class="form-signin" method="post" action="/admin/auth"><h2><?php echo $menus['main']['logo']['name']; ?></h2>
    <?php include (ABSOLUTE_PATH.'/admin/form.php'); ?>
    <button type="submit" class="btn btn-sm btn-primary btn-block my-1">Register</button>
    <a class="btn btn-sm btn-outline-primary btn-block my-1" href="/admin/auth?section=signin">Sign in</a>
  </form>

<?php elseif ($_GET['section']=='change-password'): ?>
  <form class="form-signin" method="post" action="/admin/auth?action=change-password"><h2><?php echo $menus['main']['logo']['name']; ?></h2>
  <h4 class="my-3 font-weight-normal"><span class="fas fa-key"></span>&nbsp;Change password</h4>
  <label for="inputEmail" class="sr-only">Email address</label>
  <input type="email" id="inputEmail" class="form-control my-1" placeholder="Email address" required disabled="disabled">
  <label for="inputOldPassword" class="sr-only">Old password</label>
  <input type="password" id="inputOldPassword" class="form-control my-1" placeholder="Old password" autofocus>
  <label for="inputNewPassword" class="sr-only">New password</label>
  <input type="password" id="inputNewPassword" class="form-control my-1" placeholder="New password">
  <label for="inputConfirmNewPassword" class="sr-only">Confirm new password</label>
  <input type="password" id="inputConfirmNewPassword" class="form-control my-1" placeholder="Confirm new password">
  <a class="btn btn-sm btn-primary btn-block my-1" href="/admin/auth?action=change-password">Submit</a>
    <p class="text-muted small my-5"><?php echo '<a href="'.BASE_URL.'"><span class="fas fa-angle-double-left"></span>&nbsp;'.$menus['main']['logo']['name'].'</a>'; ?></p>
    <p class="text-muted small my-5">&copy; <?php echo (date('Y')=='2020'?date('Y'):'2020 - '.date('Y')); ?> Wildfire</p>
  </form>

<?php elseif ($_GET['section']=='edit-profile'): ?>
  <form class="form-signin" method="post" action="/admin/auth?action=edit-profile"><h2><?php echo $menus['main']['logo']['name']; ?></h2>
  <h4 class="my-3 font-weight-normal"><span class="fas fa-user"></span>&nbsp;Edit profile</h4>
  <label for="inputEmail" class="sr-only">Email address</label>
  <input type="email" id="inputEmail" class="form-control my-1" placeholder="Email address" required disabled="disabled">
  <label for="inputFullName" class="sr-only" autofocus>Full name</label>
  <input type="text" id="inputFullName" class="form-control my-1" placeholder="Full name">
  <a class="btn btn-sm btn-primary btn-block my-1" href="/admin/auth?action=edit-profile">Submit</a>
    <p class="text-muted small my-5"><?php echo '<a href="'.BASE_URL.'"><span class="fas fa-angle-double-left"></span>&nbsp;'.$menus['main']['logo']['name'].'</a>'; ?></p>
    <p class="text-muted small my-5">&copy; <?php echo (date('Y')=='2020'?date('Y'):'2020 - '.date('Y')); ?> Wildfire</p>
  </form>

<?php elseif ($_GET['section']=='forgot-password'): ?>
  <form class="form-signin" method="post" action="/admin/auth?action=forgot-password"><h2><?php echo $menus['main']['logo']['name']; ?></h2>
  <h4 class="my-3 font-weight-normal"><span class="fas fa-key"></span>&nbsp;Forgot password</h4>
  <label for="inputEmail" class="sr-only">Email address</label>
  <input type="email" id="inputEmail" class="form-control my-1" placeholder="Email address" required autofocus>
  <a class="btn btn-sm btn-primary btn-block my-1" href="/admin/auth?action=generate-password">Generate password</a>
  <a class="btn btn-sm btn-outline-primary btn-block my-1" href="/admin/auth?section=register">Register</a>
  <p class="text-muted small my-1"><a href="/admin/auth?section=forgot-password"><span class="fas fa-lock"></span>&nbsp;Sign in</a></p>
    <p class="text-muted small my-5"><?php echo '<a href="'.BASE_URL.'"><span class="fas fa-angle-double-left"></span>&nbsp;'.$menus['main']['logo']['name'].'</a>'; ?></p>
    <p class="text-muted small my-5">&copy; <?php echo (date('Y')=='2020'?date('Y'):'2020 - '.date('Y')); ?> Wildfire</p>
  </form>

<?php else: ?>
  <form class="form-signin" method="post" action="/admin/auth?action=signin"><h2><?php echo $menus['main']['logo']['name']; ?></h2>
  <h4 class="my-3 font-weight-normal"><span class="fas fa-lock"></span>&nbsp;Sign in</h4>
  <label for="inputEmail" class="sr-only">Email address</label>
  <input type="email" name="email" id="inputEmail" class="form-control my-1" placeholder="Email address" required autofocus>
  <label for="inputPassword" class="sr-only">Password</label>
  <input type="password" name="password" id="inputPassword" class="form-control my-1" placeholder="Password" required>
  <div class="checkbox my-1 small"><label><input type="checkbox" class="my-0" value="remember-me"> Remember me</label></div>
  <button type="submit" class="btn btn-sm btn-primary btn-block my-1">Sign in</button>
  <a class="btn btn-sm btn-outline-primary btn-block my-1" href="/admin/auth?section=register">Register</a>
  <p class="text-muted small my-2"><a href="/admin/auth?section=forgot-password"><span class="fas fa-key"></span>&nbsp;Forgot password?</a></p>
    <p class="text-muted small my-5"><?php echo '<a href="'.BASE_URL.'"><span class="fas fa-angle-double-left"></span>&nbsp;'.$menus['main']['logo']['name'].'</a>'; ?></p>
    <p class="text-muted small my-5">&copy; <?php echo (date('Y')=='2020'?date('Y'):'2020 - '.date('Y')); ?> Wildfire</p>
  </form>

<?php endif; ?>

</body>
</html>
