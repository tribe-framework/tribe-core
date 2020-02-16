<!doctype html>
<html lang="<?php echo $_SESSION['language']; ?>">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<title><?php echo $page_title; ?></title>
	<meta name="description" content="<?php echo $page_description; ?>">

	<link href="/plugins/bootstrap-4.3.1-dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="/plugins/fontawesome-free-5.12.0-web/css/all.min.css" rel="stylesheet">
	<link href="/plugins/datatables-1.10.20/datatables.min.css" rel="stylesheet">
	<link href="/css/custom.css" rel="stylesheet">
</head>

<body>

<nav class="navbar navbar-expand-lg navbar-light bg-light">
	<a class="navbar-brand" href="/">Website</a>
		<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
		<span class="navbar-toggler-icon"></span>
	</button>

	<div class="collapse navbar-collapse" id="navbarSupportedContent">
		<ul class="navbar-nav mr-auto">
			<li class="nav-item active">
				<a class="nav-link" href="#">Home</a>
			</li>
			<li class="nav-item">
				<a class="nav-link" href="#">Link</a>
			</li>
			<li class="nav-item dropdown">
				<a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
				Dropdown
				</a>
				<div class="dropdown-menu" aria-labelledby="navbarDropdown">
				<a class="dropdown-item" href="#">Action</a>
				<a class="dropdown-item" href="#">Another action</a>
				<div class="dropdown-divider"></div>
				<a class="dropdown-item" href="#">Something else here</a>
				</div>
				</li>
				<li class="nav-item">
				<a class="nav-link disabled" href="#" tabindex="-1" aria-disabled="true">Disabled</a>
			</li>
		</ul>

		<form class="form-inline my-2 my-lg-0">
			<input class="form-control mr-sm-2" type="search" placeholder="Search" aria-label="Search">
			<button class="btn btn-outline-success my-2 my-sm-0" type="submit">Search</button>
		</form>
	</div>
</nav>