<!doctype html>
<html lang="<?php echo $types['webapp']['lang']; ?>">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

	<title><?php echo (isset($postdata_modified[$headmeta_title]) ? $postdata_modified[$headmeta_title] : $types['webapp']['headmeta_title']); ?></title>
	<meta name="description" content="<?php echo (isset($postdata_modified[$headmeta_description]) ? $postdata_modified[$headmeta_description] : $types['webapp']['headmeta_description']); ?>">
	<meta property="og:title" content="<?php echo (isset($postdata_modified[$headmeta_title]) ? $postdata_modified[$headmeta_title] : $types['webapp']['headmeta_title']); ?>">
	<meta property="og:description" content="<?php echo (isset($postdata_modified[$headmeta_description]) ? $postdata_modified[$headmeta_description] : $types['webapp']['headmeta_description']); ?>">
	<meta property="og:image" content="<?php echo ($postdata['cover_media'][0]?$postdata['cover_media'][0]:$postdata['cover_media']); ?>">

	<link href="/plugins/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="/plugins/fontawesome/css/all.min.css" rel="stylesheet">
	
	<link href="<?php echo THEME_URL; ?>/css/custom.css?v=<?php echo time(); ?>" rel="stylesheet">
</head>

<body>
<?php echo $theme->get_navbar_menu('main'); ?>