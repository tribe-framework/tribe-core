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

	function get_navbar_menu ($slug='', $css_classes=array('navbar'=>'navbar-expand-md navbar-light bg-light', 'ul'=>'navbar-nav ml-auto mr-0', 'li'=>'nav-item', 'a'=>'nav-link', 'toggler'=>'navbar-toggler'), $hamburger_bars='<span class="navbar-toggler-icon"></span>') {
		global $menus, $types, $dash;
		
		if (is_array($slug))
			$items=$slug;
		else if ($slug)
			$items=$menus[$slug];
		else
			$items=0;

		$op='';

		if ($items) {
			$op.='
			<nav class="navbar '.$css_classes['navbar'].'">
				<a class="navbar-brand" href="'.($items['logo']['href']??'#').'" title="'.($items['logo']['title']??BASE_URL.' logo').'">'.(isset($items['logo']['src']) && trim($items['logo']['src'])?'<img '.(isset($items['logo']['height'])?'height="'.$items['logo']['height'].'"':'').' src="'.$items['logo']['src'].'">':(isset($items['logo']['name'])?$items['logo']['name'].(isset($items['logo']['byline'])?'<span class="small byline">'.$items['logo']['byline'].'</span>':''):'Wildfire')).'</a>
				<button class="'.($css_classes['toggler']??'').'" type="button" data-toggle="collapse" data-target="#navbarSupportedContent">
					'.$hamburger_bars.'
				</button>
				<div class="collapse navbar-collapse" id="navbarSupportedContent">
					<ul class="'.$css_classes['ul'].'">';
					if (isset($items['menu'])) {
						foreach ($items['menu'] as $item) {
							if (is_array(($item['submenu']??''))) {
								$op.='<li class="'.($css_classes['li']??'').' dropdown"><a class="'.($css_classes['a']??'').' dropdown-toggle" href="#" title="'.($item['title']??'').'" role="button" data-toggle="dropdown">'.($item['name']??'').'
									</a><div class="dropdown-menu '.($css_classes['dropdown']??'').' '.($item['dropdown_class']??'').'">';
								if (isset($item['submenu'])) {
									foreach ($item['submenu'] as $subitem)
										$op.='<a class="dropdown-item" href="'.($subitem['href']??'').'" title="'.($subitem['title']??'').'">'.($subitem['name']??'').'</a>';
								}
								$op.='</div></li>';
							}
							else if (isset_var($item['submenu'])) {
								$submenu=$item['submenu'];
								$is_user_role_menu=0;
								if (isset_var($types[$submenu]['roles']) && is_array($types[$submenu]['roles'])) {
									$subitems=$types[$submenu]['roles'];
									$is_user_role_menu=1;
								}
								else
									$subitems=$dash::get_all_ids(isset_var($item['submenu']), isset_var($types[$submenu]['priority_field']), isset_var($types[$submenu]['priority_order']));
								$op.='<li class="'.isset_var($css_classes['li']).' dropdown"><a class="'.isset_var($css_classes['a']).' dropdown-toggle" href="#" title="'.isset_var($item['title']).'" role="button" data-toggle="dropdown">'.isset_var($item['name']).'
									</a><div class="dropdown-menu '.isset_var($css_classes['dropdown']).' '.isset_var($item['dropdown_class']).'">';
								foreach ($subitems as $key=>$opt) {
									if ($is_user_role_menu) {
										$subitem=$opt;
										$subitem['href']='/admin/list?type='.$item['submenu'].'&role='.$key;
									}
									else {
										$subitem=$dash::get_content($opt['id']);
										$subitem['href']='/'.$item['submenu'].'/'.$subitem['slug'];
									}
									$op.='<a class="dropdown-item" href="'.isset_var($subitem['href']).'">'.isset_var($subitem['title']).'</a>';
								}
								$op.='</div></li>';
							}
							else {
								$data_ext='';
								if ($item['data']) {
									foreach ($item['data'] as $data) {
										foreach ($data as $k=>$v) {
											$data_ext.='data-'.$k.'="'.$v.'" ';
										}
									}
								}

								$op.='<li class="'.isset_var($css_classes['li']).'"><a class="'.isset_var($css_classes['a']).'" '.isset_var($data_ext).' href="'.isset_var($item['href']).'" title="'.isset_var($item['title']).'">'.isset_var($item['name']).'</a></li>';
							}

						}
					}
					$op.='
					</ul>
				</div>
			</nav>';
		}
		else {
			$op.='
			<nav class="navbar '.isset_var($css_classes['navbar']).'">
				<a class="navbar-brand" href="/">Website</a>
					<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
					<span class="navbar-toggler-icon"></span>
				</button>

				<div class="collapse navbar-collapse" id="navbarSupportedContent">
					<ul class="navbar-nav mr-auto">
						<li class="'.isset_var($css_classes['li']).' active">
							<a class="'.isset_var($css_classes['a']).'" href="#">Home</a>
						</li>
						<li class="'.isset_var($css_classes['li']).'">
							<a class="'.isset_var($css_classes['a']).'" href="#">Link</a>
						</li>
						<li class="'.isset_var($css_classes['li']).' dropdown">
							<a class="'.isset_var($css_classes['a']).' dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
							Dropdown
							</a>
							<div class="dropdown-menu '.isset_var($css_classes['dropdown']).' '.isset_var($item['dropdown_class']).'" aria-labelledby="navbarDropdown">
							<a class="dropdown-item" href="#">Action</a>
							<a class="dropdown-item" href="#">Another action</a>
							<div class="dropdown-divider"></div>
							<a class="dropdown-item" href="#">Something else here</a>
							</div>
							</li>
							<li class="'.isset_var($css_classes['li']).'">
							<a class="'.isset_var($css_classes['a']).' disabled" href="#" tabindex="-1" aria-disabled="true">Disabled</a>
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

	function get_menu ($slug='', $css_classes=array('ul'=>'justify-content-center', 'li'=>'nav-item', 'a'=>'nav-link')) {
		global $menus, $types, $dash;
		
		if (is_array($slug))
			$items=$slug;
		else if ($slug)
			$items=$menus[$slug];
		else
			$items=0;

		$op='';

		if ($items) {
			$op.='
			<ul class="'.$css_classes['ul'].'">';	
					foreach ($items['menu'] as $item) {
						if (isset_var($item['submenu']) && is_array($item['submenu'])) {
							$op.='<li class="dropdown '.$css_classes['li'].'"><a class="'.$css_classes['a'].' dropdown-toggle" href="#" title="'.$item['title'].'" role="button" data-toggle="dropdown">'.$item['name'].'
								</a><div class="dropdown-menu '.$css_classes['dropdown'].' '.$item['dropdown_class'].'">';
							foreach ($item['submenu'] as $subitem)
								$op.='<a class="dropdown-item" href="'.$subitem['href'].'" title="'.($subitem['title']?$subitem['title']:'').'">'.$subitem['name'].'</a>';
							$op.='</div></li>';
						}
						else if (isset_var($item['submenu'])) {
							$submenu=$item['submenu'];
							$subitems=$dash::get_all_ids($item['submenu'], isset_var($types[$submenu]['priority_field']), isset_var($types[$submenu]['priority_order']));
							$op.='<li class="dropdown '.isset_var($css_classes['li']).'"><a class="'.isset_var($css_classes['a']).' dropdown-toggle" href="#" title="'.isset_var($item['title']).'" role="button" data-toggle="dropdown">'.isset_var($item['name']).'
								</a><div class="dropdown-menu '.isset_var($css_classes['dropdown']).' '.isset_var($item['dropdown_class']).'">';
							foreach ($subitems as $opt) {
								$subitem=$dash::get_content($opt['id']);
								$op.='<a class="dropdown-item" href="/'.$item['submenu'].'/'.$subitem['slug'].'">'.isset_var($subitem['title']).'</a>';
							}
							$op.='</div></li>';
						}
						else {
							$data_ext='';
							if (isset_var($item['data'])) {
								foreach ($item['data'] as $data) {
									foreach ($data as $k=>$v)
										$data_ext.='data-'.$k.'="'.$v.'" ';
								}
							}
							
							$op.='<li class="'.isset_var($css_classes['li']).'"><a class="'.isset_var($css_classes['a']).'" '.isset_var($data_ext).' href="'.isset_var($item['href']).'" title="'.isset_var($item['title']).'">'.isset_var($item['name']).'</a></li>';
						}

					}
					$op.='
					</ul>';
		}
		else {
			$op.='
					<ul class="'.isset_var($css_classes['ul']).'">
					  <li class="'.isset_var($css_classes['li']).'">
					    <a class="'.isset_var($css_classes['a']).' active" href="#">Active</a>
					  </li>
					  <li class="'.isset_var($css_classes['li']).'">
					    <a class="'.isset_var($css_classes['a']).'" href="#">Link</a>
					  </li>
					  <li class="'.isset_var($css_classes['li']).'">
					    <a class="'.isset_var($css_classes['a']).'" href="#">Link</a>
					  </li>
					  <li class="'.isset_var($css_classes['li']).'">
					    <a class="'.isset_var($css_classes['a']).' disabled" href="#" tabindex="-1" aria-disabled="true">Disabled</a>
					  </li>
					</ul>';
		}
		
		return $op;
	}
}
?>