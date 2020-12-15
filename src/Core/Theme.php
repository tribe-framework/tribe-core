<?php
/*
functions start with push_, pull_, get_, do_ or is_
push_ is to save to database
pull_ is to pull from database, returns 1 or 0, saves the output array in $last_data
get_ is to get usable values from functions
do_ is for action that doesn't have a database push or pull
is_ is for a yes/no answer
 */

namespace Wildfire\Core;

class Theme {

	public static $last_error = null; //array of error messages
	public static $last_info = null; //array of info messages
	public static $last_data = null; //array of data to be sent for display
	public static $last_redirect = null; //redirection url

	public function __construct() {
		$this->dash = new Dash();
	}

	public function get_navbar_menu($slug = '', $css_classes = array('navbar' => 'navbar-expand-md navbar-light bg-light', 'ul' => 'navbar-nav ml-auto mr-0', 'li' => 'nav-item', 'a' => 'nav-link', 'toggler' => 'navbar-toggler'), $hamburger_bars = '<span class="navbar-toggler-icon"></span>') {

		$types = $this->dash->getTypes();
		$menus = $this->dash->getMenus();
		$session_user = $this->dash->getSessionUser();

		if (is_array($slug)) {
			$items = $slug;
		} else if ($slug) {
			$items = $menus[$slug];
		} else {
			$items = 0;
		}

		$op = '';

		if ($items) {
			$op .= '
			<nav class="navbar ' . $css_classes['navbar'] . '">
				<a class="navbar-brand" href="' . ($items['logo']['href'] ?? '#') . '" title="' . ($items['logo']['title'] ?? BASE_URL . ' logo') . '">' . (isset($items['logo']['src']) && trim($items['logo']['src']) ? '<img ' . (isset($items['logo']['height']) ? 'height="' . $items['logo']['height'] . '"' : '') . ' src="' . $items['logo']['src'] . '">' : (isset($items['logo']['name']) ? $items['logo']['name'] . (isset($items['logo']['byline']) ? '<span class="small byline">' . $items['logo']['byline'] . '</span>' : '') : 'Wildfire')) . '</a>
				<button class="' . ($css_classes['toggler'] ?? '') . '" type="button" data-toggle="collapse" data-target="#navbarSupportedContent">
					' . $hamburger_bars . '
				</button>
				<div class="collapse navbar-collapse" id="navbarSupportedContent">
					<ul class="' . $css_classes['ul'] . '">';
			if (isset($items['menu'])) {
				foreach ($items['menu'] as $item) {
					if (($item['admin_access_only'] ?? false) && $types['user']['roles'][$session_user[role_slug]]['role'] != 'admin') {
						continue;
					}

					if (is_array(($item['submenu'] ?? ''))) {
						$op .= '<li class="' . ($css_classes['li'] ?? '') . ' dropdown"><a class="' . ($css_classes['a'] ?? '') . ' dropdown-toggle" href="#" title="' . ($item['title'] ?? '') . '" role="button" data-toggle="dropdown">' . ($item['name'] ?? '') . '
									</a><div class="dropdown-menu ' . ($css_classes['dropdown'] ?? '') . ' ' . ($item['dropdown_class'] ?? '') . '">';
						if (isset($item['submenu'])) {
							foreach ($item['submenu'] as $subitem) {
								$op .= '<a class="dropdown-item" href="' . ($subitem['href'] ?? '') . '" title="' . ($subitem['title'] ?? '') . '">' . ($subitem['name'] ?? '') . '</a>';
							}

						}
						$op .= '</div></li>';
					} else if (isset($item['submenu'])) {
						$submenu = $item['submenu'];
						$is_user_role_menu = 0;
						if (is_array(($types[$submenu]['roles'] ?? ''))) {
							$subitems = $types[$submenu]['roles'];
							$is_user_role_menu = 1;
						} else {
							$subitems = $this->dash->get_all_ids(($item['submenu'] ?? ''), ($types[$submenu]['priority_field'] ?? ''), ($types[$submenu]['priority_order'] ?? ''));
						}

						$op .= '<li class="' . ($css_classes['li'] ?? '') . ' dropdown"><a class="' . ($css_classes['a'] ?? '') . ' dropdown-toggle" href="#" title="' . ($item['title'] ?? '') . '" role="button" data-toggle="dropdown">' . ($item['name'] ?? '') . '
									</a><div class="dropdown-menu ' . ($css_classes['dropdown'] ?? '') . ' ' . ($item['dropdown_class'] ?? '') . '">';
						if (is_array($subitems)) {
							foreach ($subitems as $key => $opt) {
								if ($is_user_role_menu) {
									$subitem = $opt;
									$subitem['href'] = '/admin/list?type=' . $item['submenu'] . '&role=' . $key;
								} else {
									$subitem = $this->dash->get_content($opt['id']);
									$subitem['href'] = '/' . $item['submenu'] . '/' . $subitem['slug'];
								}
								$op .= '<a class="dropdown-item" href="' . ($subitem['href'] ?? '') . '">' . ($subitem['title'] ?? '') . '</a>';
							}
						}
						$op .= '</div></li>';
					} else {
						$data_ext = '';
						if (isset($item['data'])) {
							foreach ($item['data'] as $data) {
								foreach ($data as $k => $v) {
									$data_ext .= 'data-' . $k . '="' . $v . '" ';
								}
							}
						}

						$op .= '<li class="' . ($css_classes['li'] ?? '') . '"><a class="' . ($css_classes['a'] ?? '') . '" ' . ($data_ext ?? '') . ' href="' . ($item['href'] ?? '') . '" title="' . ($item['title'] ?? '') . '">' . ($item['name'] ?? '') . '</a></li>';
					}

				}
			}
			$op .= '
					</ul>
				</div>
			</nav>';
		} else {
			$op .= '
			<nav class="navbar ' . ($css_classes['navbar'] ?? '') . '">
				<a class="navbar-brand" href="/">Website</a>
					<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
					<span class="navbar-toggler-icon"></span>
				</button>

				<div class="collapse navbar-collapse" id="navbarSupportedContent">
					<ul class="navbar-nav mr-auto">
						<li class="' . ($css_classes['li'] ?? '') . ' active">
							<a class="' . ($css_classes['a'] ?? '') . '" href="#">Home</a>
						</li>
						<li class="' . ($css_classes['li'] ?? '') . '">
							<a class="' . ($css_classes['a'] ?? '') . '" href="#">Link</a>
						</li>
						<li class="' . ($css_classes['li'] ?? '') . ' dropdown">
							<a class="' . ($css_classes['a'] ?? '') . ' dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
							Dropdown
							</a>
							<div class="dropdown-menu ' . ($css_classes['dropdown'] ?? '') . ' ' . ($item['dropdown_class'] ?? '') . '" aria-labelledby="navbarDropdown">
							<a class="dropdown-item" href="#">Action</a>
							<a class="dropdown-item" href="#">Another action</a>
							<div class="dropdown-divider"></div>
							<a class="dropdown-item" href="#">Something else here</a>
							</div>
							</li>
							<li class="' . ($css_classes['li'] ?? '') . '">
							<a class="' . ($css_classes['a'] ?? '') . ' disabled" href="#" tabindex="-1" aria-disabled="true">Disabled</a>
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

	public function get_menu($slug = '', $css_classes = array('ul' => 'justify-content-center', 'li' => 'nav-item', 'a' => 'nav-link')) {

		$types = $this->dash->getTypes();
		$menus = $this->dash->getMenus();
		$session_user = $this->dash->getSessionUser();

		if (is_array($slug)) {
			$items = $slug;
		} else if ($slug) {
			$items = $menus[$slug];
		} else {
			$items = 0;
		}

		$op = '';

		if ($items) {
			$op .= '
			<ul class="' . ($css_classes['ul'] ?? '') . '">';
			if (isset($items['menu'])) {
				foreach ($items['menu'] as $item) {
					if ($item['admin_access_only'] && $types['user']['roles'][$session_user[role_slug]]['role'] != 'admin') {
						continue;
					}

					if (is_array(($item['submenu'] ?? ''))) {
						$op .= '<li class="dropdown ' . ($css_classes['li'] ?? '') . '"><a class="' . ($css_classes['a'] ?? '') . ' dropdown-toggle" href="#" title="' . ($item['title'] ?? '') . '" role="button" data-toggle="dropdown">' . ($item['name'] ?? '') . '
								</a><div class="dropdown-menu ' . ($css_classes['dropdown'] ?? '') . ' ' . ($item['dropdown_class'] ?? '') . '">';
						if (isset($item['submenu'])) {
							foreach ($item['submenu'] as $subitem) {
								$op .= '<a class="dropdown-item" href="' . ($subitem['href'] ?? '') . '" title="' . ($subitem['title'] ?? '') . '">' . ($subitem['name'] ?? '') . '</a>';
							}

						}
						$op .= '</div></li>';
					} else if (isset($item['submenu'])) {
						$submenu = $item['submenu'];
						$subitems = $this->dash->get_all_ids($item['submenu'], ($types[$submenu]['priority_field'] ?? ''), ($types[$submenu]['priority_order'] ?? ''));
						$op .= '<li class="dropdown ' . ($css_classes['li'] ?? '') . '"><a class="' . ($css_classes['a'] ?? '') . ' dropdown-toggle" href="#" title="' . ($item['title'] ?? '') . '" role="button" data-toggle="dropdown">' . ($item['name'] ?? '') . '
								</a><div class="dropdown-menu ' . ($css_classes['dropdown'] ?? '') . ' ' . ($item['dropdown_class'] ?? '') . '">';
						foreach ($subitems as $opt) {
							$subitem = $this->dash->get_content($opt['id']);
							$op .= '<a class="dropdown-item" href="/' . $item['submenu'] . '/' . $subitem['slug'] . '">' . ($subitem['title'] ?? '') . '</a>';
						}
						$op .= '</div></li>';
					} else {
						$data_ext = '';
						if (isset($item['data'])) {
							foreach ($item['data'] as $data) {
								foreach ($data as $k => $v) {
									$data_ext .= 'data-' . $k . '="' . $v . '" ';
								}

							}
						}

						$op .= '<li class="' . ($css_classes['li'] ?? '') . '"><a class="' . ($css_classes['a'] ?? '') . '" ' . ($data_ext) . ' href="' . ($item['href'] ?? '') . '" title="' . ($item['title'] ?? '') . '">' . ($item['name'] ?? '') . '</a></li>';
					}

				}
			}
			$op .= '
				</ul>';
		} else {
			$op .= '
					<ul class="' . ($css_classes['ul'] ?? '') . '">
					  <li class="' . ($css_classes['li'] ?? '') . '">
					    <a class="' . ($css_classes['a'] ?? '') . ' active" href="#">Active</a>
					  </li>
					  <li class="' . ($css_classes['li'] ?? '') . '">
					    <a class="' . ($css_classes['a'] ?? '') . '" href="#">Link</a>
					  </li>
					  <li class="' . ($css_classes['li'] ?? '') . '">
					    <a class="' . ($css_classes['a'] ?? '') . '" href="#">Link</a>
					  </li>
					  <li class="' . ($css_classes['li'] ?? '') . '">
					    <a class="' . ($css_classes['a'] ?? '') . ' disabled" href="#" tabindex="-1" aria-disabled="true">Disabled</a>
					  </li>
					</ul>';
		}

		return $op;
	}
}
