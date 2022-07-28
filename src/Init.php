<?php

namespace Wildfire\Core;

use \Wildfire\Auth;
use \Wildfire\Api as Api;

class Init {
    // properties
    protected $error404_file;
    protected $defaultPagesDir = THEME_PATH . "/pages";
    protected static $types;
    protected static $type;
    protected static $slug;
    protected static $menus;
    protected static $currentUser;

    public function __construct() {
        // enable http only cookie to prevent misuse by xss
        ini_set( 'session.cookie_httponly', 1 );
        session_save_path('/tmp');
        session_start();

        $auth = new Auth();
        self::$currentUser = $auth->getCurrentUser();

        $this->error404_file = THEME_PATH . '/pages/404.php';

        if ('staging' == strtolower($_ENV['ENV'])) {
            error_reporting(E_ALL);
            ini_set('display_errors', 0);
            ini_set('display_startup_errors', 0);
        } else if ('dev' == strtolower($_ENV['ENV'])) {
            error_reporting(E_ALL);
            ini_set('display_errors', 1);
            ini_set('display_startup_errors', 1);
        } else {
            ini_set('display_errors', 0);
            ini_set('display_startup_errors', 0);
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

        if (file_exists(ABSOLUTE_PATH . $uri . '.php')) {
            include_once ABSOLUTE_PATH . $uri . '.php';
            return true;
        }

        // for theme
        if (
            $_SERVER['SERVER_NAME'] == $_ENV['WEB_BARE_URL'] ||
            $_SERVER['SERVER_NAME'] == "www.{$_ENV['WEB_BARE_URL']}" ||
            $_SERVER['HTTP_HOST'] == $_ENV['WEB_BARE_URL']
        ) {
            //handing main domain
            if ($uri ?? false) {
                if (preg_match('/^\//', $uri)) {
                    $uri = substr($uri, 1);
                }

                $ext = explode('/', $uri);
                if (count($ext)) {
                    self::$type = $dash->do_unslugify($ext[0]);
                    if (self::$type == 'user') {
                        self::$type = 'auth';
                    }

                }

                if (count($ext) > 1) {
                    self::$slug = $dash->do_unslugify($ext[1]);
                }

            } elseif ($_GET['type'] ?? false) {
                // for dashboard
                self::$type = $dash->do_unslugify($_GET['type']);
            }
        } elseif (strstr($_SERVER['SERVER_NAME'], $_ENV['WEB_BARE_URL'])) {
            //handling subdomains - use subdomain as a type name in types.json
            //replace - (hyphen) in URL, with _ (underscore) in types.json
            //to handle sub-sub-domains use sub.subdomain as a type name

            self::$type = str_replace('.' . $_ENV['WEB_BARE_URL'], '', $_SERVER['SERVER_NAME']);

            if (self::$type == 'user') {
                self::$type = 'auth';
            }

            if (preg_match('/^\//', $uri)) {
                $uri = substr($uri, 1);
            }

            $ext = explode('/', $uri);

            if (trim($ext[0])) {
                self::$slug = $dash->do_unslugify($ext[0]);
            }
        }

        $this->init();
    }

    /**
     * @name init
     * @desc to initialise this class
     */
    private function init() {
        $type = self::$type;
        $slug = self::$slug;

        define('AUTH_PATH', ABSOLUTE_PATH . '/vendor/wildfire/auth');
        define('AUTH_URL', BASE_URL . '/vendor/wildfire/auth');

        define('ADMIN_PATH', ABSOLUTE_PATH . '/vendor/wildfire/admin');
        define('ADMIN_URL', BASE_URL . '/vendor/wildfire/admin/theme/assets');

        /**
         * load theme functions if it exists
         */
        $theme_functions = THEME_PATH . '/includes/functions.php';
        if (file_exists($theme_functions)) {
            include_once $theme_functions;
        } else {
            $theme_functions_old = THEME_PATH . '/functions.php';
            include_once $theme_functions_old;
            unset($theme_functions_old);
        }
        unset($theme_functions);

        if (($type ?? '') == 'api' && !$_ENV['SKIP_TRIBE_API']) {
            return $this->loadApi();
        }

        if (($type ?? '') == 'admin') {
            return $this->loadAdmin();
        }

        if (($type ?? '') == 'auth') {
            return $this->loadAuth();
        }

        if (($type ?? '') == 'search') {
            return $this->loadSearch();
        }

        if (($type ?? '') == 'sitemap.xml') {
            return $this->loadSitemap();
        }

        if (($type ?? '') == 'backup') {
            return $this->loadBackup();
        }

        if (isset($type) && isset($slug)) {
            if ($type=='theme' && $slug=='api')
                return $this->loadThemeApi();
            else
                return $this->loadTypeSlugFile();
        }

        if ($type ?? false) {
            return $this->loadTypeFile();
        }

        return $this->loadIndex();
    }

    private function loadSitemap() {
        $file_path = ABSOLUTE_PATH . '/vendor/wildfire/sitemap/index.php';

        if (file_exists($file_path)) {
            include_once $file_path;
            return true;
        }

        $this->errorNotFound();
    }

    private function loadBackup() {
        $file_path = ABSOLUTE_PATH . '/vendor/wildfire/backup/index.php';

        if (file_exists($file_path)) {
            include_once $file_path;
            return true;
        }

        $this->errorNotFound();
    }

    /**
     * @name loadApi
     * @desc loads theme/pages/api/index.php file for api requests
     */
    private function loadApi() {
        $url_parts = array_values(
            array_filter(
                explode('/', $_SERVER['REQUEST_URI'])
            )
        );

        // strip off the "api" prefix to url
        if (strtolower($url_parts[0]) == 'api') {
            unset($url_parts[0]);
            $url_parts = array_values($url_parts);
        }

        $api = new Api;
        $api->exposeTribeApi($url_parts, array_keys(self::$types));

        return;
    }

    /**
     * @name loadThemeApi
     * @desc loads theme/api/index.php file for api requests
     */
    private function loadThemeApi() {
        $dash = new Dash;
        
        $url_parts = array_values(
            array_filter(
                explode('/', $_SERVER['REQUEST_URI'])
            )
        );

        unset($url_parts[0]);
        unset($url_parts[1]);
        $url_parts = array_values($url_parts);

        $type = $dash->do_unslugify($url_parts[0]);
        $slug = $dash->do_unslugify($url_parts[1]);

        if (file_exists(THEME_PATH . "/api/index.php"))
            require_once THEME_PATH . "/api/index.php";

        return;
    }

    /**
     * @name loadAdmin
     * @desc loads admin file for admin requests
     */
    private function loadAdmin() {
        $type = self::$type;
        $slug = (self::$slug ?? 'index') ?: 'index';

        // whitelisted type/slug path for admin
        $admin_file = ABSOLUTE_PATH . "/vendor/wildfire/$type/theme/pages/$slug.php";
        $admin_api = ABSOLUTE_PATH . "/vendor/wildfire/$type/theme/api/$slug.php";

        if (file_exists($admin_file)) {
            include_once $admin_file;
        } else if (file_exists($admin_api)) {
            include_once $admin_api;
        } else {
            $this->errorNotFound();
        }

        unset($admin_file);
        return true;
    }

    /**
     * @name loadAuth
     * @desc loads auth file for auth requests
     */
    private function loadAuth() {
        $type = self::$type;
        $slug = (self::$slug ?? 'index') ?: 'index';

        if (file_exists(TRIBE_ROOT . "/vendor/wildfire/{$type}/theme/pages/{$slug}.php")) {
            require_once TRIBE_ROOT . "/vendor/wildfire/{$type}/theme/pages/{$slug}.php";
        } else if (file_exists(TRIBE_ROOT . "/vendor/wildfire/$type/api/$slug.php")) {
            require_once TRIBE_ROOT . "/vendor/wildfire/$type/api/$slug.php";
        } else if (\file_exists(THEME_PATH . "/pages/user/{$slug}.php")) {
            require_once THEME_PATH . "/pages/user/{$slug}.php";
        } else if (\file_exists(AUTH_PATH . '/theme/pages/404.php')) {
            require_once AUTH_PATH . '/theme/pages/404.php';
        } else {
            echo "404 / Resource not found";
        }

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
        $postdata = $dash->getObject($search_param);
        unset($search_param);

        $postdata_modified = $postdata;

        $typedata = $types[$type];
        $headmeta_title = $typedata['headmeta_title'] ?? '';
        $headmeta_description = $typedata['headmeta_description'] ?? '';
        $headmeta_img_url = $typedata['headmeta_image_url'] ?? '';

        $append_phrase = '';
        if ($typedata['headmeta_title_append']) {
            foreach ($typedata['headmeta_title_append'] as $a) {
                $key = $a['type'];
                $value = $a['slug'];

                $_glue = $typedata['headmeta_title_glue'] ?? '';
                $_typeKey = $types[$key][$value] ?? '';
                $append_phrase .= " {$_glue} {$_typeKey}";
            }
        }

        $prepend_phrase = '';
        if ($typedata['headmeta_title_prepend']) {
            foreach ($typedata['head_title_prepend'] as $p) {
                $key = $p['type'];
                $val = $p['slug'];
                $prepend_phrase .= "{$typedata[$key][$val]} {$typedata['headmeta_title_glue']} ";
            }
        }

        if ($postdata_modified && ($append_phrase || $prepend_phrase)) {
            if (!empty($headmeta_title)) {
                $postdata_modified[$headmeta_title] = $prepend_phrase . $postdata[$headmeta_title] . $append_phrase;
            }
            if (!empty($headmeta_description)) {
                $postdata_modified[$headmeta_description] = trim(strip_tags($postdata_modified[$headmeta_description] ?? ''));
            }
        }

        if ($postdata_modified && (strlen($postdata_modified[$headmeta_description]) > 160)) {
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

        if (isset($headmeta_image_url) && !($meta_image_url = $postdata_modified[$headmeta_image_url][0] ?? null)) {
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

        // checking for "/theme/pages/$type/$slug.php"
        $file_path = THEME_PATH . "/pages/$type/$slug.php";

        if (file_exists($file_path)) {
            include_once $file_path;
            unset($file_path);
            return true;
        }

        // checking for "/theme/pages/$type-$slug.php"
        $file_path = THEME_PATH . "/pages/$type-$slug.php";

        if (file_exists($file_path)) {
            include_once $file_path;
            unset($file_path);
            return true;
        }

        // checking for "/theme/$type-$slug.php"
        $file_path = THEME_PATH . "/$type-$slug.php";

        if (file_exists($file_path)) {
            include_once $file_path;
            unset($file_path);
            return true;
        }

        // checking for "/theme/pages/single-ID.php"
        $file_path = THEME_PATH . "/pages/single-{$postdata['id']}.php";

        if (file_exists($file_path)) {
            include_once $file_path;
            unset($file_path);
            return true;
        }

        // checking for "/theme/single-ID.php"
        $file_path = THEME_PATH . "/single-{$postdata['id']}.php";

        if (file_exists($file_path)) {
            include_once $file_path;
            unset($file_path);
            return true;
        }

        // checking for "/theme/includes/$type/_$slug.php"
        $file_path = THEME_PATH . "/includes/$type/_$slug.php";

        if (file_exists($file_path)) {
            include_once $file_path;
            unset($file_path);
            return true;
        }

        // checking for "/theme/includes/_$type-$slug.php"
        $file_path = THEME_PATH . "/includes/_$type-$slug.php";

        if (file_exists($file_path)) {
            include_once $file_path;
            unset($file_path);
            return true;
        }

        // checking for "/theme/includes/_single-ID.php"
        $file_path = THEME_PATH . "/includes/_single-{$postdata['id']}.php";

        if (file_exists($file_path)) {
            include_once $file_path;
            unset($file_path);
            return true;
        }

        // checking for "/theme/pages/single-$type.php"
        $file_path = THEME_PATH . "/pages/single-$type.php";

        if (file_exists($file_path)) {
            include_once $file_path;
            unset($file_path);
            return true;
        }

        // checking for "/theme/single-$type.php"
        $file_path = THEME_PATH . "/single-$type.php";

        if (file_exists($file_path)) {
            include_once $file_path;
            unset($file_path);
            return true;
        }

        // checking for generic "/theme/pages/single.php"
        $file_path = THEME_PATH . '/pages/single.php';

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

        unset($file_path);

        // if loading a file fails, just load a 404 page
        return $this->errorNotFound();
    }

    /**
     * @desc loads files for a particular type
     */
    private function loadTypeFile() {
        $types = self::$types;
        $type = self::$type;
        $typedata = $types[$type] ?? null;

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
         * or you can simply host it under "pages/$type" inside "/theme/"
         */

        // checking for "type/index.php" under "/theme/pages"
        $file_path = THEME_PATH . "/pages/$type/index.php";

        if (file_exists($file_path)) {
            include_once $file_path;
            unset($file_path);
            return true;
        }

        // checking for "type.php" under "/theme/pages"
        $file_path = THEME_PATH . "/pages/$type.php";

        if (file_exists($file_path)) {
            include_once $file_path;
            unset($file_path);
            return true;
        }

        if (!$typedata) {
            return $this->errorNotFound();
        }

        // checking for "$type.php" under "/theme"
        $file_path = THEME_PATH . "/$type.php";

        if (file_exists($file_path)) {
            include_once $file_path;
            unset($file_path);
            return true;
        }

        // checking for "archive-$type.php" under "/theme/pages"
        $file_path = THEME_PATH . "/pages/archive-$type.php";

        if (file_exists($file_path)) {
            include_once $file_path;
            unset($file_path);
            return true;
        }

        // checking for "archive-$type.php" under "/theme"
        $file_path = THEME_PATH . "/archive-$type.php";

        if (file_exists($file_path)) {
            include_once $file_path;
            unset($file_path);
            return true;
        }

        // checking for "archive.php" under "/theme/pages"
        $file_path = THEME_PATH . '/pages/archive.php';

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

        unset($file_path);

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
        return self::$currentUser;
    }
}
