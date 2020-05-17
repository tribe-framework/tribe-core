<!doctype html>
<html lang="<?php echo $types['webapp']['lang']; ?>">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<title><?php echo 'Wildfire'.(isset($headmeta_title)?' / '.$headmeta_title:''); ?></title>
	<meta name="description" content="Wildfire admin dashboard for <?php echo (isset($headmeta_title)?' / '.$headmeta_title:''); ?>">

	<link href="/plugins/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="/plugins/fontawesome/css/all.min.css" rel="stylesheet">
	<link href="/plugins/datatables/datatables.min.css" rel="stylesheet">
	<link href="<?php echo BASE_URL; ?>/admin/css/custom.css?v=<?php echo time(); ?>" rel="stylesheet">
</head>

<body>
<?php echo $theme->get_navbar_menu('main'); ?>