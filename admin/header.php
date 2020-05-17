<!doctype html>
<html lang="<?php echo $types['webapp']['lang']; ?>">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<title><?php echo 'Wildfire'.(isset($headmeta_title)?' / '.$headmeta_title:''); ?></title>
	<meta name="description" content="Wildfire admin dashboard for <?php echo (isset($headmeta_title)?' / '.$headmeta_title:''); ?>">
	<link rel="stylesheet" href="https://use.typekit.net/xkh7dxd.css">
	<link href="<?php echo BASE_URL; ?>/admin/css/bootstrap.min.css" rel="stylesheet">
	<link href="<?php echo BASE_URL; ?>/admin/css/wildfire.css" rel="stylesheet">
	<link href="/plugins/fontawesome/css/all.min.css" rel="stylesheet">
	<link href="/plugins/datatables/datatables.min.css" rel="stylesheet">
	<link href="<?php echo BASE_URL; ?>/admin/css/custom.css?v=<?php echo time(); ?>" rel="stylesheet">
</head>

<body>
<?php echo $theme->get_navbar_menu($admin_menus['admin_menu'], array('navbar'=>'navbar-expand-md navbar-dark bg-dark p-3', 'ul'=>'navbar-nav ml-auto mr-0', 'li'=>'nav-item', 'a'=>'nav-link text-white'), '<span class="fas fa-bars"></span>'); ?>