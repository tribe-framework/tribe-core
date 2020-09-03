<?php
$type='user';
if (($types['webapp']['user_theme']??false) && file_exists(THEME_PATH.'/user-header.php')):
	include_once (THEME_PATH.'/user-header.php');
else: ?>
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
	<link href="<?php echo BASE_URL; ?>/admin/css/custom.css" rel="stylesheet">
  	<link href="<?php echo BASE_URL; ?>/admin/css/user.css" rel="stylesheet">
</head>

<body class="text-center">
	<hr class="hr fixed-top" style="margin:0 !important;">
<?php endif; ?>