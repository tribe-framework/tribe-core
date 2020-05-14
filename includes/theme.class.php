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

	function get_navbar_menu ($slug='', $css_classes=array('navbar'=>'navbar-expand-md navbar-light bg-light')) {
		global $menus, $types, $dash;
		
		if ($slug)
			$items=$menus[$slug];
		else
			$items=0;

		$op='';

		if ($items) {
			$op.='
			<nav class="navbar '.$css_classes['navbar'].'">
				<a class="navbar-brand" href="'.$items['logo']['href'].'" title="'.$items['logo']['title'].'">'.(isset($items['logo']['src'])?'<img height="'.$items['logo']['height'].'" src="'.$items['logo']['src'].'">':$items['logo']['name']).'</a>
					<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent">
					<span class="navbar-toggler-icon"></span>
				</button>
				<div class="collapse navbar-collapse" id="navbarSupportedContent">
					<ul class="navbar-nav ml-auto mr-0">';			
					foreach ($items['menu'] as $item) {
						if (is_array($item['submenu'])) {
							$op.='<li class="nav-item dropdown"><a class="nav-link dropdown-toggle" href="#" title="'.$item['title'].'" role="button" data-toggle="dropdown">'.$item['name'].'
								</a><div class="dropdown-menu '.$css_classes['dropdown'].' '.$item['dropdown_class'].'">';
							foreach ($item['submenu'] as $subitem)
								$op.='<a class="dropdown-item" href="'.$subitem['href'].'" title="'.($subitem['title']?$subitem['title']:'').'">'.$subitem['name'].'</a>';
							$op.='</div></li>';
						}
						else if ($item['submenu']) {
							$submenu=$item['submenu'];
							$subitems=$dash::get_all_ids($item['submenu'], (isset($types[$submenu]['priority_field'])?$types[$submenu]['priority_field']:''), (isset($types[$submenu]['priority_order'])?$types[$submenu]['priority_order']:''));
							$op.='<li class="nav-item dropdown"><a class="nav-link dropdown-toggle" href="#" title="'.$item['title'].'" role="button" data-toggle="dropdown">'.$item['name'].'
								</a><div class="dropdown-menu '.$css_classes['dropdown'].' '.$item['dropdown_class'].'">';
							foreach ($subitems as $opt) {
								$subitem=$dash::get_content($opt['id']);
								$op.='<a class="dropdown-item" href="/'.$item['submenu'].'/'.$subitem['slug'].'">'.($subitem['title']?$subitem['title']:'').'</a>';
							}
							$op.='</div></li>';
						}
						else {
							$op.='<li class="nav-item"><a class="nav-link" href="'.$item['href'].'" title="'.$item['title'].'">'.$item['name'].'</a></li>';
						}

					}
					$op.='
					</ul>
				</div>
			</nav>';
		}
		else {
			$op.='
			<nav class="navbar '.$css_classes['navbar'].'">
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
							<div class="dropdown-menu '.$css_classes['dropdown'].' '.$item['dropdown_class'].'" aria-labelledby="navbarDropdown">
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

					<form class="form-inline my-2 my-md-0">
						<input class="form-control mr-sm-2" type="search" placeholder="Search" aria-label="Search">
						<button class="btn btn-outline-success my-2 my-sm-0" type="submit">Search</button>
					</form>
				</div>
			</nav>';
		}
		
		return $op;
	}

	function get_menu ($slug='', $css_classes=array('ul'=>'justify-content-center')) {
		global $menus, $types, $dash;
		
		if ($slug)
			$items=$menus[$slug];
		else
			$items=0;

		$op='';

		if ($items) {
			$op.='
			<ul class="nav '.$css_classes['ul'].'">';	
					foreach ($items['menu'] as $item) {
						if (is_array($item['submenu'])) {
							$op.='<li class="nav-item dropdown '.$css_classes['li'].'"><a class="nav-link dropdown-toggle" href="#" title="'.$item['title'].'" role="button" data-toggle="dropdown">'.$item['name'].'
								</a><div class="dropdown-menu '.$css_classes['dropdown'].' '.$item['dropdown_class'].'">';
							foreach ($item['submenu'] as $subitem)
								$op.='<a class="dropdown-item" href="'.$subitem['href'].'" title="'.($subitem['title']?$subitem['title']:'').'">'.$subitem['name'].'</a>';
							$op.='</div></li>';
						}
						else if ($item['submenu']) {
							$submenu=$item['submenu'];
							$subitems=$dash::get_all_ids($item['submenu'], (isset($types[$submenu]['priority_field'])?$types[$submenu]['priority_field']:''), (isset($types[$submenu]['priority_order'])?$types[$submenu]['priority_order']:''));
							$op.='<li class="nav-item dropdown '.$css_classes['li'].'"><a class="nav-link dropdown-toggle" href="#" title="'.$item['title'].'" role="button" data-toggle="dropdown">'.$item['name'].'
								</a><div class="dropdown-menu '.$css_classes['dropdown'].' '.$item['dropdown_class'].'">';
							foreach ($subitems as $opt) {
								$subitem=$dash::get_content($opt['id']);
								$op.='<a class="dropdown-item" href="/'.$item['submenu'].'/'.$subitem['slug'].'">'.($subitem['title']?$subitem['title']:'').'</a>';
							}
							$op.='</div></li>';
						}
						else {
							$op.='<li class="nav-item '.$css_classes['li'].'"><a class="nav-link" href="'.$item['href'].'" title="'.$item['title'].'">'.$item['name'].'</a></li>';
						}

					}
					$op.='
					</ul>';
		}
		else {
			$op.='
					<ul class="nav justify-content-center">
					  <li class="nav-item">
					    <a class="nav-link active" href="#">Active</a>
					  </li>
					  <li class="nav-item">
					    <a class="nav-link" href="#">Link</a>
					  </li>
					  <li class="nav-item">
					    <a class="nav-link" href="#">Link</a>
					  </li>
					  <li class="nav-item">
					    <a class="nav-link disabled" href="#" tabindex="-1" aria-disabled="true">Disabled</a>
					  </li>
					</ul>';
		}
		
		return $op;
	}
}
?>