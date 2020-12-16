<?php

namespace Wildfire\Core;

class Init {
	// properties
	protected $error404_file = THEME_PATH . '/errors/404.php';
	protected static $types;
	protected static $type;
	protected static $slug;
	protected static $menus;
	protected static $session_user;

	public function __construct() {
		session_save_path('/tmp');
		session_start();
		self::$session_user = $_SESSION['user'] ?? null;

		// browser debugging
		if (defined('ENV') && (ENV == 'dev')) {
			ini_set('display_errors', 1);
			ini_set('display_startup_errors', 1);
			error_reporting(E_ALL);
		} else {
			ini_set('display_errors', 0);
			ini_set('display_startup_errors', 0);
			error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
		}

		$dash = new Dash();

		self::$types = $dash->get_types(ABSOLUTE_PATH . '/config/types.json');
		self::$menus = json_decode(file_get_contents(ABSOLUTE_PATH . '/config/menus.json'), true);

		if (!isset($this->types['webapp']['lang'])) {
			self::$types['webapp']['lang'] = 'en';
		}

		$uri = urldecode(
			parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)
		);

		// for theme
		if ($uri ?? false) {
			$uri = str_replace('/user/', '/auth/', $uri);

			if (preg_match('/^\//', $uri)) {
				$uri = substr($uri, 1);
			}
			$ext = explode('/', $uri);

			if (count($ext)) {
				self::$type = $dash->do_unslugify($ext[0]);
			}

			if (count($ext) > 1) {
				self::$slug = $dash->do_unslugify($ext[1]);
			}
		} elseif ($_GET['type'] ?? false) {
			// for dashboard
			self::$type = $dash->do_unslugify($_GET['type']);
		}
	}

	/**
	 * @name init
	 * @desc to initialise this class
	 */
	public function init() {
		$type = self::$type;
		$slug = self::$slug;

		/**
		 * load theme functions if it exists
		 */
		$theme_functions = THEME_PATH . '/includes/functions.php';
		if (file_exists($theme_functions)) {
			include_once $theme_functions;
		}
		unset($theme_functions);

		if (($type ?? '') == 'scss') {
			$this->loadScss();
		}

		if (($type ?? '') == 'admin') {
			$this->loadAdmin();
		}

		if (($type ?? '') == 'auth') {
			$this->loadAuth();
		}

		if (($type ?? '') == 'search') {
			return $this->loadSearch();
		}

		if (($type ?? '') == 'sitemap.xml') {
			$this->loadSitemap();
		}

		if (
			(isset($type) && isset($slug)) ||
			$type == 'user'
		) {
			return $this->loadTypeSlugFile();
		}

		if ($type ?? false) {
			return $this->loadTypeFile();
		}

		return $this->loadIndex();
	}

	private function loadScss() {
		$file_path = THEME_PATH . '/assets/scss/init.php';

		if (file_exists($file_path)) {
			include_once $file_path;
			return true;
		}

		$this->errorNotFound();
	}

	private function loadSitemap() {
		$file_path = ABSOLUTE_PATH . '/vendor/wildfire/sitemap/index.php';

		if (file_exists($file_path)) {
			include_once $file_path;
			return true;
		}

		$this->errorNotFound();
	}

	/**
	 * @name loadAdmin
	 * @desc loads admin file for admin requests
	 */
	private function loadAdmin() {
		$type = self::$type;
		$slug = self::$slug;

		if (!$slug) {
			$slug = 'index';
		}

		// load the search file from theme
		$admin_file = ABSOLUTE_PATH . '/vendor/wildfire/' . $type . '/' . $slug . '.php';
		if (!file_exists($admin_file)) {
			die('The file does not exist.');
		}

		include_once $admin_file;
		unset($admin_file);
		return true;
	}

	/**
	 * @name loadAuth
	 * @desc loads auth file for auth requests
	 */
	private function loadAuth() {
		$type = self::$type;
		$slug = self::$slug;

		if (!$slug) {
			$slug = 'index';
		}

		// load the search file from theme
		$auth_file = ABSOLUTE_PATH . '/vendor/wildfire/' . $type . '/' . $slug . '.php';
		if (!file_exists($auth_file)) {
			die('The file does not exist.');
		}

		include_once $auth_file;
		unset($auth_file);
		return true;
	}

	/**
	 * @name loadSearch
	 * @desc loads search file for search requests
	 */
	private function loadSearch() {
		$slug = self::$slug;
		/**
		 * if a slug exists and query string 'q' is missing,
		 * then use the slug as the query string 'q'
		 */
		if ($slug && !$_GET['q']) {
			$_GET['q'] = $slug;
		}

		// load the search file from theme
		$search_file = THEME_PATH . '/search.php';
		if (!file_exists($search_file)) {
			die('Include a "search.php" file in your UI directory');
		}

		include_once $search_file;
		unset($search_file);
		return true;
	}

	/**
	 * @name loadAuth
	 * @desc loads auth/init that handles logic for user auth
	 */
	private function loadAuth() {
		$type = self::$type;
		$slug = self::$slug;

		if (!$slug) {
			$auth_file = ABSOLUTE_PATH . '/vendor/wildfire/auth/init.php';

			if (!file_exists($auth_file)) {
				die('"wildfire\auth" is missing');
			}
		} else {
			$auth_file = ABSOLUTE_PATH . '/vendor/wildfire/auth/' . $slug . '.php';

			if (!file_exists($auth_file)) {
				$this->errorNotFound();
			}
		}

		include_once $auth_file;
		unset($auth_file);
		return true;
	}

	/**
	 * @name loadTypeSlugFile
	 * @desc loads files by parsing type & slug in URL
	 */
	private function loadTypeSlugFile() {
		$types = self::$types;
		$type = self::$type;
		$slug = self::$slug;
		$error404_file = $this->error404_file;

		$dash = new Dash;

		// get postdata from db using type and slug
		$search_param = ['type' => $type, 'slug' => $slug];
		$postdata = $dash->get_content($search_param);
		unset($search_param);

		$postdata_modified = $postdata;

		$typedata = $types[$type];
		$headmeta_title = $typedata['headmeta_title'];
		$headmeta_description = $typedata['headmeta_description'];
		$headmeta_img_url = $typedata['headmeta_image_url'];

		if ($typedata['headmeta_title_append']) {
			foreach ($typedata['headmeta_title_append'] as $a) {
				$key = $a['type'];
				$value = $a['slug'];

				$append_phrase .= ' ' . $typedata['headmeta_title_glue'] . ' ' . $types[$key][$value];
			}
		}

		if ($typedata['headmeta_title_prepend']) {
			foreach ($typedata['head_title_prepend'] as $p) {
				$key = $p['type'];
				$val = $p['slug'];
				$prepend_phrase .= $typedata[$key][$val] . ' ' . $typedata['headmeta_title_glue'] . ' ';
			}
		}

		if ($append_phrase || $prepend_phrase) {
			$postdata_modified[$headmeta_title] = $prepend_phrase . $postdata[$headmeta_title] . $append_phrase;
			$postdata_modified[$headmeta_description] = trim(strip_tags($postdata_modified[$headmeta_description]));
		}

		if (strlen($postdata_modified[$headmeta_description]) > 160) {
			$postdata_modified[$headmeta_description] = substr($postdata_modified[$headmeta_description], 0, 154) . '[...]';
		}

		if (!($meta_title = $postdata_modified[$headmeta_title] ?? null)) {
			if (!($meta_title = $types['webapp']['headmeta_title'] ?? null)) {
				$meta_title = '';
			}
		}

		if (!($meta_description = $postdata_modified[$headmeta_description] ?? null)) {
			if (!($meta_description = $types['webapp']['headmeta_description'] ?? null)) {
				$meta_description = '';
			}

		}

		if (!($meta_image_url = $postdata_modified[$headmeta_image_url][0] ?? null)) {
			if (!($meta_image_url = $postdata_modified[$headmeta_image_url] ?? null)) {
				if (!($meta_image_url = $types['webapp']['headmeta_image_url'] ?? null)) {
					$meta_image_url = '';
				}
			}
		}

		/**
		 * in "/theme" you can have
		 * single-ID or single-$slug for a specific post
		 * single-type template for all posts
		 *
		 * or you can simply host pages under "pages/$slug",
		 * inside "/theme/"
		 */

		if ($type == 'user') {
			if (!$slug) {
				$auth_file = ABSOLUTE_PATH . '/vendor/wildfire/auth/init.php';

				if (!file_exists($auth_file)) {
					die('"wildfire\auth" is missing');
				}
			} else {
				$auth_file = ABSOLUTE_PATH . '/vendor/wildfire/auth/' . $slug . '.php';

				if (!file_exists($auth_file)) {
					$this->errorNotFound();
				}
			}

			include_once $auth_file;
			unset($auth_file);
			return true;
		}

		// checking for "/theme/pages/$type/$slug.php"
		$file_path = THEME_PATH . '/pages/' . $type . '/' . $slug . '.php';

		if (file_exists($file_path)) {
			include_once $file_path;
			unset($file_path);
			return true;
		}

		// checking for "/theme/$type-$slug.php"
		$file_path = THEME_PATH . '/' . $type . '-' . $slug . '.php';

		if (file_exists($file_path)) {
			include_once $file_path;
			unset($file_path);
			return true;
		}

		// checking for "/theme/single-ID.php"
		$file_path = THEME_PATH . '/single-' . $postdata['id'] . '.php';

		if (file_exists($file_path)) {
			include_once $file_path;
			unset($file_path);
			return true;
		}

		// checking for "/theme/single-$type.php"
		$file_path = THEME_PATH . '/single-' . $type . '.php';

		if (file_exists($file_path)) {
			include_once $file_path;
			unset($file_path);
			return true;
		}

		// checking for generic "/theme/single.php"
		$file_path = THEME_PATH . '/single.php';

		if (file_exists($file_path)) {
			include_once $file_path;
			unset($file_path);
			return true;
		}

		// if none of $type files exist, just load "/theme/index.php"
		unset($file_path);
		return $this->loadIndex();

		// if loading a file fails, just load a 404 page
		return $this->errorNotFound();
	}

	/**
	 * @name loadTypeFile
	 * @desc loads files for a particular type
	 */
	private function loadTypeFile() {
		$types = self::$types;
		$type = self::$type;
		$typedata = $types[$type];
		$error404_file = $this->error404_file;

		// if typedata isn't available then load the 404 page
		if (!$typedata) {
			return $this->errorNotFound();
		}

		$dash = new Dash();

		$postids = $dash->get_all_ids($type);

		if (!($meta_title = $typedata['meta_title'] ?? null)) {
			if (!($meta_title = $types['webapp']['headmeta_title'] ?? null)) {
				$meta_title = '';
			}
		}

		if (!($meta_description = $typedata['meta_description'] ?? null)) {
			if (!($meta_description = $types['webapp']['headmeta_description'] ?? null)) {
				$meta_description = '';
			}
		}

		if (!($meta_image_url = $typedata['meta_image_url'] ?? null)) {
			if (!($meta_image_url = $types['webapp']['headmeta_image_url'] ?? null)) {
				$meta_image_url = '';
			}
		}

		/**
		 * archive-$type is a template for how the type is listed,
		 * and is to not be confused with single-ID
		 *
		 * in "/theme" you can have
		 * archive-$type.php or archive.php
		 *
		 * or you can simply host it under "templates/$type" inside "/theme/"
		 */

		// checking for "type.php" under "/theme/templates"
		$file_path = THEME_PATH . '/templates/' . $type . '.php';

		if (file_exists($file_path)) {
			include_once $file_path;
			unset($file_path);
			return true;
		}

		// checking for "$type.php" under "/theme"
		$file_path = THEME_PATH . '/' . $type . '.php';

		if (file_exists($file_path)) {
			include_once $file_path;
			unset($file_path);
			return true;
		}

		// checking for "archive-$type.php" under "/theme"
		$file_path = THEME_PATH . '/archive-' . $type . '.php';

		if (file_exists($file_path)) {
			include_once $file_path;
			unset($file_path);
			return true;
		}

		// checking for "archive.php" under "/theme"
		$file_path = THEME_PATH . '/archive.php';

		if (file_exists($file_path)) {
			include_once $file_path;
			unset($file_path);
			return true;
		}

		// if none of the archive files are found, load index
		unset($file_path);
		return $this->loadIndex($types);

		// show 404 if everything above fails
		return $this->errorNotFound();
	}

	/**
	 * @name loadIndex
	 * @desc loads "/theme/index.php"
	 */
	private function loadIndex() {
		$types = self::$types;
		$meta_title = $types['webapp']['headmeta_title'] ?? '';
		$meta_description = $types['webapp']['headmeta_description'] ?? '';
		$meta_image_url = $types['webapp']['headmeta_image_url'] ?? '';

		// checking for index under /theme/pages
		$file_path = THEME_PATH . '/pages/index.php';
		if (file_exists($file_path)) {
			include_once $file_path;
			return true;
		}

		// checking for index under /theme
		$file_path = THEME_PATH . '/index.php';
		if (file_exists($file_path)) {
			include_once $file_path;
			return true;
		}

		return $this->errorNotFound();
	}

	/**
	 * @name errorNotFound
	 * @desc handles 404 error page
	 */
	private function errorNotFound() {
		$error404_file = $this->error404_file;

		if (file_exists($error404_file)) {
			include_once $error404_file;
			return false;
		} else {
			die('Resource not available on server');
		}
	}

	public function getTypes() {
		return self::$types;
	}

	public function getMenus() {
		return self::$menus;
	}

	public function getType() {
		return self::$type;
	}

	public function getSlug() {
		return self::$slug;
	}

	public function getSessionUser() {
		return self::$session_user;
	}
}
