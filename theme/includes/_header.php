<?php
$use_custom_theme = $types['webapp']['user_theme'] ?? false;

if ($use_custom_theme && file_exists(THEME_PATH . '/pages/user/_header.php')):
	include_once THEME_PATH . '/pages/user/_header.php';
elseif ($use_custom_theme && file_exists(THEME_PATH . '/pages/user/header.php')):
	include_once THEME_PATH . '/pages/user/header.php';
elseif ($use_custom_theme && file_exists(THEME_PATH . '/user-header.php')):
	include_once THEME_PATH . '/user-header.php';
else:
?>

<!doctype html>
<html lang="<?=$types['webapp']['lang']?>">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<title><?=(isset($headmeta_title) ? $headmeta_title . ' &raquo; ' : '') . 'Dashboard'?></title>
	<meta
		name="description"
		content="Access authorisation<?=isset($headmeta_title) ? ' for ' . $headmeta_title : ''?>"
	>
	<link rel="stylesheet" href="https://use.typekit.net/xkh7dxd.css">
	<link rel="stylesheet" href="/vendor/wildfire/auth/theme/assets/css/bootstrap.min.css">
	<link rel="stylesheet" href="/vendor/wildfire/auth/theme/assets/css/wildfire.css">
	<link rel="stylesheet" href="/vendor/wildfire/auth/theme/assets/plugins/fontawesome/css/all.min.css">
	<link rel="stylesheet" href="/vendor/wildfire/auth/theme/assets/css/custom.css">
  	<link rel="stylesheet" href="/vendor/wildfire/auth/theme/assets/css/user.css">
</head>

<body class="text-center">
	<hr class="hr fixed-top" style="margin:0 !important;">

<?php endif;?>
