<!doctype html>
<html lang="<?php echo $_SESSION['language']; ?>">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<title><?php echo $page_title; ?></title>
	<meta name="description" content="<?php echo $page_description; ?>">

	<link href="/plugins/bootstrap/css/bootstrap.min.css" rel="stylesheet">
	<link href="/plugins/fontawesome/css/all.min.css" rel="stylesheet">
	<link href="/plugins/datatables/datatables.min.css" rel="stylesheet">
	<link href="<?php echo THEME_URL; ?>/css/custom.css?v=<?php echo time(); ?>" rel="stylesheet">
</head>

<body>
<?php echo $theme->get_menu('main'); ?>