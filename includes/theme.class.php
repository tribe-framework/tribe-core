<?php
/*
	functions start with push_, pull_, get_, do_ or is_
	push_ is to save to database
	pull_ is to pull from database, returns 1 or 0, saves the output array in $last_data
	get_ is to get usable values from functions
	do_ is for action that doesn't have a database push or pull
	is_ is for a yes/no answer
*/

class theme {  

	public static $last_error = null; //array of error messages
	public static $last_info = null; //array of info messages
	public static $last_data = null; //array of data to be sent for display
	public static $last_redirect = null; //redirection url

	function __construct () {
		
	}

	function get_menu ($slug='') {
		if ($slug) {
			$all_items=json_decode(file_get_contents('menus.json', true));
			$items=$all_items->{$slug};
		}
		else
			$items=0;

		$op='';

		if ($items) {
			$op.='
			<nav class="navbar navbar-expand-lg navbar-light bg-light">
				<a class="navbar-brand" href="'.$items->logo->href.'" title="'.$items->logo->title.'">'.($items->logo->src?'<img height="'.$items->logo->height.'" src="'.$items->logo->src.'">':$items->logo->name).'</a>
					<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent">
					<span class="navbar-toggler-icon"></span>
				</button>
				<div class="collapse navbar-collapse" id="navbarSupportedContent">
					<ul class="navbar-nav ml-auto mr-0">';			
					foreach ($items->menu as $item) {
						if (empty($item->submenu))
							$op.='<li class="nav-item active"><a class="nav-link" href="'.$item->href.'" title="'.$item->title.'">'.$item->name.'</a></li>';
						else {
							$op.='<li class="nav-item dropdown"><a class="nav-link dropdown-toggle" href="#" title="'.$item->title.'" role="button" data-toggle="dropdown">'.$item->name.'
								</a><div class="dropdown-menu dropdown-menu-right">';
							foreach ($item->submenu as $subitem)
								$op.='<a class="dropdown-item" href="'.$subitem->href.'" title="'.($subitem->title?$subitem->title:'').'">'.$subitem->name.'</a>';
							$op.='</div></li>';
						}
					}
					$op.='
					</ul>
				</div>
			</nav>';
		}
		else {
			$op.='
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
			</nav>';
		}
		
		return $op;
	}
}
?>