<?php
include_once ('../config-init.php'); ?>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<title><?php echo 'Wildfire Entity'.(isset($headmeta_title)?' &raquo; '.$headmeta_title:''); ?></title>
	<meta name="description" content="Access authorisation<?php echo (isset($headmeta_title)?' for '.$headmeta_title:''); ?>">
	<link rel="stylesheet" href="https://use.typekit.net/xkh7dxd.css">
	<link href="<?php echo BASE_URL; ?>/admin/css/bootstrap.min.css" rel="stylesheet">
	<link href="<?php echo BASE_URL; ?>/admin/css/wildfire.css" rel="stylesheet">
	<link href="/plugins/fontawesome/css/all.min.css" rel="stylesheet">
	<link href="/plugins/datatables/datatables.min.css" rel="stylesheet">
	<link href="<?php echo BASE_URL; ?>/admin/css/custom.css" rel="stylesheet">
  <link href="<?php echo BASE_URL; ?>/admin/css/auth.css" rel="stylesheet">
  <meta name="theme-color" content="#563d7c">
</head>

<body class="text-center">
  <hr class="hr fixed-top" style="margin:0 !important;">
  <form class="form-signin">
  <h2><?php echo $menus['main']['logo']['name']; ?></h2>
  <h4 class="my-3 font-weight-normal"><span class="fas fa-sign-in-alt"></span>&nbsp;Sign in</h4>
  <label for="inputEmail" class="sr-only">Email address</label>
  <input type="email" id="inputEmail" class="form-control" placeholder="Email address" required autofocus>
  <label for="inputPassword" class="sr-only">Password</label>
  <input type="password" id="inputPassword" class="form-control" placeholder="Password" required>
  <div class="checkbox mb-3">
    <label>
      <input type="checkbox" value="remember-me"> Remember me
    </label>
  </div>
  <a class="btn btn-sm btn-primary btn-block" href="/admin">Sign in</a>
  <a class="btn btn-sm btn-primary btn-block" href="/admin">Register</a>
  <p class="text-muted small my-5"><?php echo '<a href="'.BASE_URL.'"><span class="fas fa-angle-double-left"></span>&nbsp;'.$menus['main']['logo']['name'].'</a>'; ?></p>
  <p class="text-muted small my-5">&copy; <?php echo (date('Y')=='2020'?date('Y'):'2020 - '.date('Y')); ?> Wildfire</p>
  </form>
</body>
</html>
