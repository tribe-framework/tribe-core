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
	<link href="<?php echo BASE_URL; ?>/admin/css/custom.css?v=<?php echo time(); ?>" rel="stylesheet">
</head>

<body>
<meta name="theme-color" content="#563d7c">

<style>
  .bd-placeholder-img {
    font-size: 1.125rem;
    text-anchor: middle;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    user-select: none;
  }

  @media (min-width: 768px) {
    .bd-placeholder-img-lg {
      font-size: 3.5rem;
    }
  }
html,
body {
  height: 100%;
}

body {
  display: -ms-flexbox;
  display: flex;
  -ms-flex-align: center;
  align-items: center;
  padding-top: 40px;
  padding-bottom: 40px;
  background-color: #f5f5f5;
}

.form-signin {
  width: 100%;
  max-width: 330px;
  padding: 15px;
  margin: auto;
}
.form-signin .checkbox {
  font-weight: 400;
}
.form-signin .form-control {
  position: relative;
  box-sizing: border-box;
  height: auto;
  padding: 10px;
  font-size: 16px;
}
.form-signin .form-control:focus {
  z-index: 2;
}
.form-signin input[type="email"] {
  margin-bottom: -1px;
  border-bottom-right-radius: 0;
  border-bottom-left-radius: 0;
}
.form-signin input[type="password"] {
  margin-bottom: 10px;
  border-top-left-radius: 0;
  border-top-right-radius: 0;
}

    </style>

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
  <a class="btn btn-primary btn-block" href="/admin">Sign in</a>
  <a class="btn btn-primary btn-block" href="/admin">Register</a>
  <p class="text-muted small my-5"><?php echo '<a href="'.BASE_URL.'"><span class="fas fa-angle-double-left"></span>&nbsp;'.$menus['main']['logo']['name'].'</a>'; ?></p>
  <p class="text-muted small my-5">&copy; <?php echo (date('Y')=='2020'?date('Y'):'2020 - '.date('Y')); ?> Wildfire</p>
  </form>
</body>
</html>
