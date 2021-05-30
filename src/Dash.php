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

use Wildfire\Core\Init;
use Wildfire\Core\MySQL;

class Dash extends Init {
    public static $last_error = null; //array of error messages
    public static $last_info = null; //array of info messages
    public static $last_data = null; //array of data to be sent for display
    public static $last_redirect = null; //redirection url
    public $statusCode = null; // to set server response code

    public function __construct() {

        /*
        If it's a new installation, create the first user
         */
        $sql = new MySQL();
        $q = $sql->executeSQL("SELECT `id` FROM `data` WHERE `content`->'$.type'='user'");
        if (!$q[0]['id']) {
            $usr = array();
            $usr['type'] = 'user';
            $usr['role_slug'] = 'admin';
            $usr['email'] = $_ENV['CONTACT_EMAIL'];
            $usr['password'] = md5($_ENV['DB_PASS']);
            $usr['user_id'] = $this->get_unique_user_id();
            $this->push_content($usr);
        }
    }

    public function get_last_error() {
        if (count(dash::$last_error)) {
            $op = implode('<br>', dash::$last_error);
            dash::$last_error = array();
            return $op;
        } else {
            return '';
        }
    }

    public function get_last_info() {
        if (count(dash::$last_info)) {
            $op = implode('<br>', dash::$last_info);
            dash::$last_info = array();
            return $op;
        } else {
            return '';
        }
    }

    public function get_last_data() {
        $arr = dash::$last_data;
        dash::$last_data = array();
        return $arr;
    }

    public function get_last_redirect() {
        $r = dash::$last_redirect;
        dash::$last_redirect = '';
        return $r;
    }

    public function get_next_id() {
        $sql = new MySQL();
        $q = $sql->executeSQL("SELECT `id` FROM `data` WHERE 1 ORDER BY `id` DESC LIMIT 1");
        return ($q[0]['id'] + 1);
    }

    public function do_delete($post = array()) {
        $sql = new MySQL();
        $role_slug = $this->get_content_meta($post['id'], 'role_slug');
        $q = $sql->executeSQL("DELETE FROM `data` WHERE `id`='" . $post['id'] . "'");
        dash::$last_redirect = '/admin/list?type=' . $post['type'] . ($role_slug ? '&role=' . $role_slug : '');
        return 1;
    }

    public function get_ids_by_search_query($query) {
        $sql = new MySQL();
        return $sql->executeSQL("SELECT `id` FROM `data` WHERE LOWER(`content`->'$.view_searchable_data') LIKE '%" . strtolower(urldecode($query)) . "%' && `content`->'$.content_privacy'='public' GROUP BY `id` LIMIT 25");
    }

    public function push_content($post) {
        $sql = new MySQL();
        $types = self::$types;
        $updated_on = time();
        $posttype = $post['type'];

        $title_data = $this->get_type_title_data($post);
        $title_slug = $title_data['slug'];
        $title_unique = $title_data['unique'];

        //loop that doesn't break
        $post['view_searchable_data'] = '';
        foreach ($types[$posttype]['modules'] as $module) {
            //password md5 handling is a tricky game
            //connected to admin/edit.php
            //$this->get_content can mess up passwords
            if ($module['input_type'] == 'password') {
                $password_slug = $module['input_slug'];
                $password_slug_md5 = $module['input_slug'] . '_md5';

                if ($post[$password_slug] && !$post[$password_slug_md5]) {
                    if ($post['id']) {
                        //while importing from get_content function
                        $post[$password_slug] = $post[$password_slug];
                    } else {
                        //for new entries
                        $post[$password_slug] = md5($post[$password_slug]);
                    }
                } elseif ($post[$password_slug] && (md5($post[$password_slug]) != $post[$password_slug_md5])) {
                    //post edit, password changed
                    $post[$password_slug] = md5($post[$password_slug]);
                } elseif ($post[$password_slug_md5]) {
                    //post edit, when password unchanged
                    $post[$password_slug] = $post[$password_slug_md5];
                    unset($post[$password_slug_md5]);
                }
            }

            if ($module['view_searchable'] && in_array($post['type'], $types['webapp']['searchable_types']) && $post['content_privacy'] == 'public') {
                $slug = $module['input_slug'];
                if (is_array($post[$slug])) {
                    $post['view_searchable_data'] .= implode(' ', array_map('strip_tags', $post[$slug])) . ' ';
                } else {
                    $post['view_searchable_data'] .= strip_tags($post[$slug]) . ' ';
                }
            }

            //change var_type if available, before saving to database
            if ($module['var_type']) {
                $slug = $module['input_slug'];
                if ($module['var_type'] == 'int') {
                    $post[$slug] = (int) $post[$slug];
                } else if ($module['var_type'] == 'float') {
                    $post[$slug] = (float) $post[$slug];
                } else if ($module['var_type'] == 'bool') {
                    $post[$slug] = (bool) $post[$slug];
                }

            }
        }

        if ($title_unique) {
            $q = $sql->executeSQL("SELECT `id` FROM `data` WHERE `content`->'$.type'='" . $post['type'] . "' && `content`->'$." . $title_slug . "'='" . $post[$title_slug] . "'");
            if ($q[0]['id'] && $post['id'] != $q[0]['id']) {
                dash::$last_error[] = 'Either the title is left empty or the same title already exists in ' . $types[$posttype]['plural'];
                return 0;
            }
        }

        if (!trim($post['slug']) || $post['slug_update']) {
            $post['slug'] = dash::do_slugify($post[$title_slug], $title_unique);
        }

        if (!trim($post['id'])) {
            $sql->executeSQL("INSERT INTO `data` (`created_on`) VALUES ('$updated_on')");
            $post['id'] = $sql->lastInsertID();
        }

        if ($post['wp_import']) {
            $sql->executeSQL("INSERT INTO `data` (`id`, `created_on`) VALUES ('" . $post['id'] . "', '$updated_on')");
        }

        $sql->executeSQL("UPDATE `data` SET `content`='" . mysqli_real_escape_string($sql->databaseLink, json_encode($post)) . "', `updated_on`='$updated_on' WHERE `id`='" . $post['id'] . "'");
        $id = $post['id'];

        if (!trim($post['view_searchable_data'])) {
            $this->push_content_meta($post['id'], 'view_searchable_data');
        }

        dash::$last_info[] = 'Content saved.';
        dash::$last_data[] = array('updated_on' => $updated_on, 'id' => $id, 'slug' => $post['slug'], 'url' => BASE_URL . '/' . $post['type'] . '/' . $post['slug']);
        return $id;
    }

    public function get_content_meta($val, $meta_key) {
        $sql = new MySQL();

        if ($meta_key == 'id' || $meta_key == 'updated_on' || $meta_key == 'created_on') {
            $qry = "`" . $meta_key . "`";
        } else {
            $qry = "`content`->>'$." . $meta_key . "' `" . $meta_key . "`";
        }

        if (is_int($val)) {
            $q = $sql->executeSQL("SELECT " . $qry . " FROM `data` WHERE `id`='$val'");
        } else {
            $q = $sql->executeSQL("SELECT " . $qry . " FROM `data` WHERE `content`->'$.slug'='" . $val['slug'] . "' && `content`->'$.type'='" . $val['type'] . "'");
        }

        return $q[0][$meta_key];
    }

    public function push_content_meta($id, $meta_key, $meta_value = '') {
        $sql = new MySQL();
        if ($id && $meta_key) {
            if (!trim($meta_value)) {
                //to delete a key, leave it empty
                $q = $sql->executeSQL("UPDATE `data` SET `content` = JSON_REMOVE(`content`, '$." . $meta_key . "') WHERE `id`='$id'");
            } else {
                $q = $sql->executeSQL("UPDATE `data` SET `content` = JSON_SET(`content`, '$." . $meta_key . "', '$meta_value') WHERE `id`='$id'");
            }
            return 1;
        } else {
            return 0;
        }
    }

    public function get_content($val) {
        $sql = new MySQL();
        $currentUser = self::$currentUser;
        $or = array();
        if (is_int($val)) {
            $q = $sql->executeSQL("SELECT * FROM `data` WHERE `id`='$val'");
        } else {
            $q = $sql->executeSQL("SELECT * FROM `data` WHERE `content`->'$.slug'='" . $val['slug'] . "' && `content`->'$.type'='" . $val['type'] . "'");
        }

        if ($q[0]['id']) {
            $or = json_decode($q[0]['content'], true);
            $or['id'] = $q[0]['id'];
            $or['updated_on'] = $q[0]['updated_on'];
            $or['created_on'] = $q[0]['created_on'];

            if ($or['content_privacy'] == 'draft') {
                if ($currentUser['user_id'] == $or['user_id']) {
                    return $or;
                } else {
                    return 0;
                }
            } elseif ($or['content_privacy'] == 'pending') {
                if ($currentUser['role'] == 'admin' || $currentUser['user_id'] == $or['user_id']) {
                    return $or;
                } else {
                    return 0;
                }
            } else {
                return $or;
            }
        } else {
            return 0;
        }
    }

    public function fetch_content_title_array($slug, $column_key, $with_link = 1) {
        $sql = new MySQL();
        $types = self::$types;

        $q = $sql->executeSQL("SELECT `content`->'$.title' `title` FROM `data` WHERE `content`->'$.type'='$column_key' && `content`->'$.slug'='$slug'");
        if ($with_link) {
            return '<a href="' . BASE_URL . '/' . $column_key . '/' . $slug . '">' . json_decode($q[0]['title']) . '</a>';
        } else {
            return json_decode($q[0]['title']);
        }
    }

    public static function get_all_ids_count($type) {
        $sql = new MySQL();
        $currentUser = self::$currentUser;

        //user
        if (is_array($type)) {
            //accessible only to admins
            if ($currentUser['role'] != 'admin') {
                return 0;
            } else {
                $role_slug = $type['role_slug'];
                $type = $type['type'];
                $q = $sql->executeSQL("SELECT `id` FROM `data` WHERE `content`->'$.type'='$type' " . ($role_slug ? "&& `content`->'$.role_slug'='$role_slug'" : ""));
            }
        }

        //content
        else {
            $role_slug = '';
            if ($currentUser['role'] == 'admin') {
                $q = $sql->executeSQL("SELECT `id` FROM `data` WHERE `content`->'$.content_privacy'!='draft' && `content`->'$.type'='$type' " . ($role_slug ? "&& `content`->'$.role_slug'='$role_slug'" : ""));
            } else {
                $q = $sql->executeSQL("SELECT `id` FROM `data` WHERE (`content`->'$.content_privacy'='public' OR `content`->'$.user_id'='" . $currentUser['user_id'] . "') && `content`->'$.type'='$type' " . ($role_slug ? "&& `content`->'$.role_slug'='$role_slug'" : ""));
            }
        }

        return $sql->records;
    }

    /**
     * @param mixed $type
     * @param string $priority_field
     * @param string $priority_order
     * @param int $limit
     * @param boolean $debug_show_sql_statement
     * @return array
     * @return int status
     */
    public function get_all_ids(
        $type,
        $priority_field = 'id',
        $priority_order = 'DESC',
        $limit = '',
        $debug_show_sql_statement = 0
    ) {
        $sql = new MySQL();
        $currentUser = self::$currentUser;

        if ($priority_field == 'id') {
            $priority = "$priority_field $priority_order";
        } else {
            $priority = "content->'$.$priority_field' IS NULL, content->'$.$priority_field' $priority_order, id DESC";
        }

        //user
        if (is_array($type)) {
            //accessible only to admins
            if ($currentUser['role'] != 'admin') {
                return 0;
            }

            $role_slug = $type['role_slug'];
            $type = $type['type'];

            $trans = [
                '@roleSlug' => $role_slug ? " AND content->'$.role_slug'='$role_slug'" : "",
                '@limit' => $limit ? " LIMIT $limit" : "",
            ];

            $query = "SELECT id FROM data
                WHERE
                    content->'$.type'='$type'
                    @roleSlug
                    ORDER BY $priority
                    @limit
            ";

            $query = strtr($query, $trans);

            $q = $sql->executeSQL($query);
        } else {
            //content
            $role_slug = '';

            $trans = [
                '@roleSlug' => $role_slug ? " AND content->'$.role_slug'='$role_slug'" : "",
                '@limit' => $limit ? " LIMIT $limit" : "",
            ];

            if (($currentUser['role'] ?? false) == 'admin') {
                $query = "SELECT id FROM data
                    WHERE
                        content->'$.content_privacy'!='draft'
                        AND
                        content->'$.type'='$type'
                        @roleSlug
                        ORDER BY $priority
                        @limit
                ";

                $query = strtr($query, $trans);

                $q = $sql->executeSQL($query);
            } else {
                $trans['@userId'] = isset($currentUser['user_id']) ? $currentUser['user_id'] : '';

                $query = "SELECT id FROM data
                    WHERE
                        content->'$.content_privacy'='public'
                        AND
                        content->'$.type'='$type'
                        @userId
                        @roleSlug
                        ORDER BY $priority
                        @limit
                ";

                $query = strtr($query, $trans);

                $q = $sql->executeSQL($query);
            }
        }

        if ($debug_show_sql_statement) {
            echo $query;
        }

        return $q;
    }

    public function get_ids($search_arr, $comparison = 'LIKE', $between = '||', $priority_field = 'id', $priority_order = 'DESC', $limit = '', $debug_show_sql_statement = 0) {
        $sql = new MySQL();
        if ($priority_field == 'id') {
            $priority = "`" . $priority_field . "` " . $priority_order;
        } else {
            $priority = "`content`->'$." . $priority_field . "' IS NULL, `content`->'$." . $priority_field . "' " . $priority_order . ", `id` DESC";
        }

        $frechr = array();
        $i = 0;
        if (!is_array($comparison)) {
            $comparisonr = array_fill(0, count($search_arr), $comparison);
        } else {
            $comparisonr = $comparison;
        }

        foreach ($search_arr as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $kv => $vv) {
                    $frechr[] = "`content`->'$." . $kv . "' " . $comparisonr[$i] . " " . (trim($vv) ? "'" . $vv . "'" : "");
                }
            } else {
                $frechr[] = "`content`->'$." . $key . "' " . $comparisonr[$i] . " " . (trim($value) ? "'" . $value . "'" : "");
            }

            $i++;
        }

        $qry = "SELECT `id` FROM `data` WHERE `content`->'$.content_privacy'='public' AND " . join(' ' . $between . ' ', $frechr) . " ORDER BY " . $priority . ($limit ? " LIMIT " . $limit : "");
        $r = $sql->executeSQL($qry);
        if ($debug_show_sql_statement) {
            echo $qry;
        }

        return $r;
    }

    public function get_date_ids($publishing_date) {
        $sql = new MySQL();
        return $sql->executeSQL("SELECT `id` FROM `data` WHERE `content`->'$.publishing_date'='$publishing_date'");
    }

    public function do_slugify($string, $input_itself_is_unique = 0) {
        $slug = substr(strtolower(trim(preg_replace('/[^A-Za-z0-9_-]+/', '-', ($string ? $string : 'untitled')))), 0, 1500) . ($input_itself_is_unique ? '' : '-' . uniqid());
        return $slug;
    }

    public function do_unslugify($url_part) {
        return strtolower(trim(urlencode($url_part)));
    }

    public static function get_types($json_path) {
        $currentUser = self::$currentUser;

        $meta_types = json_decode('{
          "key_value_pair": {
            "slug": "key_value_pair",
            "name": "key-value pair",
            "plural": "key-value pairs",
            "description": "List of key-value pairs.",
            "disallow_editing": false,
            "modules": [
              {
                "input_slug": "title",
                "input_primary": true,
                "input_type": "text",
                "input_placeholder": "Enter remarks",
                "input_unique": false,
                "list_field": true,
                "list_searchable": true,
                "list_sortable": true
              },
              {
                "input_slug": "meta_key",
                "input_type": "text",
                "input_placeholder": "Meta Key"
              },
              {
                "input_slug": "meta_value",
                "input_type": "text",
                "input_placeholder": "Meta Value"
              }
            ]
          },
          "api_key_secret": {
            "slug": "key_value_pair",
            "name": "API key-secret pair",
            "plural": "API key-secret pairs",
            "description": "List of API key-secret pairs.",
            "disallow_editing": false,
            "modules": [
              {
                "input_slug": "title",
                "input_primary": true,
                "input_type": "text",
                "input_placeholder": "Enter remarks",
                "input_unique": false,
                "list_field": true,
                "list_searchable": true,
                "list_sortable": true
              },
              {
                "input_slug": "api_key",
                "input_type": "text",
                "input_placeholder": "API Key"
              },
              {
                "input_slug": "api_secret",
                "input_type": "text",
                "input_placeholder": "API Secret"
              }
            ]
          }
        }', true);

        $types = array_merge(json_decode(file_get_contents($json_path), true), $meta_types);
        foreach ($types as $key => $type) {
            if (!($type['slug'] == 'user' || $type['slug'] == 'webapp')) {
                if (!in_array('content_privacy', array_column($types[$key]['modules'], 'input_slug'))) {
                    if (($currentUser['role'] ?? false) == 'admin') {
                        $content_privacy_json = '{
					        "input_slug": "content_privacy",
					        "input_placeholder": "Content privacy",
					        "input_type": "select",
					        "input_options": [
					          {"slug":"public", "title":"Public link"},
					          {"slug":"private", "title":"Private link"},
					          {"slug":"pending", "title":"Submit for moderation"},
					          {"slug":"draft", "title":"Draft"}
					        ],
					        "list_field": true,
					        "input_unique": false
					    }';
                    } else {
                        $content_privacy_json = '{
					        "input_slug": "content_privacy",
					        "input_placeholder": "Content privacy",
					        "input_type": "select",
					        "input_options": [
					          {"slug":"pending", "title":"Submit for moderation"},
					          {"slug":"draft", "title":"Draft"}
					        ],
					        "list_field": true,
					        "input_unique": false
					    }';
                    }
                    $types[$key]['modules'][] = json_decode($content_privacy_json, true);
                }

                foreach ($types[$key]['modules'] as $module) {
                    if ($module['input_primary']) {
                        $types[$key]['primary_module'] = $module['input_slug'];
                        break;
                    }
                }
            }
        }
        return $types;
    }

    public function get_type_title_data($post) {
        $sql = new MySQL();
        $types = self::$types;
        $posttype = $post['type'];

        if (!($post_id = $post['id'])) {
            $last_id = $sql->executeSQL("SELECT `id` FROM `data` ORDER BY `id` DESC LIMIT 1");
            $post_id = $last_id[0]['id'] + 1;
        }
        //foreach loop that breaks
        $i = 0;
        foreach ($types[$posttype]['modules'] as $module) {
            $title = array();
            if ($module['input_primary'] && (!$module['restrict_id_max'] || $post_id <= $module['restrict_id_max']) && (!$module['restrict_id_min'] || $post_id >= $module['restrict_id_min'])) {
                $title_id = $i;
                $title['slug'] = $module['input_slug'] . (is_array($module['input_lang']) ? '_' . $module['input_lang'][0]['slug'] : '');
                $title['primary'] = $module['input_primary'];
                $title['unique'] = $module['input_unique'];
                break;
            }

            $i++;
        }
        return $title;
    }

    public function push_wp_posts($type = 'story', $meta_vars = array(), $max_records = 0, $overwrite = 0) {
        $sql = new MySQL();
        $i = 0;

        $q = $sql->executeSQL("SELECT * FROM `wp_posts` WHERE `post_status` LIKE 'publish' AND (`post_type` LIKE 'page' OR `post_type` LIKE 'post') ORDER BY `ID` ASC");

        foreach ($q as $r) {
            if ($overwrite || !$this->get_content_meta($r['ID'], 'slug')) {
                $post = $post_wp = array();
                $post['wp_import'] = 1;
                $post['id'] = $r['ID'];
                $post['type'] = $type;
                $post['title'] = $r['post_title'];
                $post['body'] = $r['post_content'];
                $post['slug'] = $r['post_name'];
                $post['content_privacy'] = 'public';
                $post['publishing_date'] = substr($r['post_date'], 0, 10);

                if ($r['post_parent']) {
                    $mv = $sql->executeSQL("SELECT `post_name` FROM `wp_posts` WHERE `ID`='" . $r['post_parent'] . "'");
                    $post_wp['post_parent'] = $mv[0]['post_name'];
                }

                foreach ($meta_vars as $var) {
                    $iv = $sql->executeSQL("SELECT `meta_value` FROM `wp_postmeta` WHERE `post_id`='" . $r['ID'] . "' && `meta_key`='$var'");
                    if ($iv[0]['meta_value']) {
                        $ivts = unserialize($iv[0]['meta_value']);
                        if (!$ivts) {
                            $iid = $iv[0]['meta_value'];
                            if (is_numeric($iid)) {
                                $mv = $sql->executeSQL("SELECT `post_name` FROM `wp_posts` WHERE `ID`='$iid'");
                                $post_wp[$var] = $mv[0]['post_name'];
                            } else {
                                $post_wp[$var] = $iid;
                            }
                        } else {
                            foreach ($ivts as $iid) {
                                if (is_numeric($iid)) {
                                    $mv = $sql->executeSQL("SELECT `post_name` FROM `wp_posts` WHERE `ID`='$iid'");
                                    $post_wp[$var][] = $mv[0]['post_name'];
                                } else {
                                    $post_wp[$var][] = $iid;
                                }
                            }
                        }
                    }
                }

                $cv = $sql->executeSQL("SELECT `guid` FROM `wp_posts` WHERE `post_parent` != 0 AND `guid` LIKE '%wp-content/uploads%' AND `post_type` LIKE 'attachment' AND `post_status` LIKE 'inherit' AND `guid` != '' AND `post_parent`='" . $r['ID'] . "' ORDER BY `ID` DESC");
                $post['files'] = array();
                foreach ($cv as $file) {
                    $ext = strtolower(pathinfo($file['guid'], PATHINFO_EXTENSION));
                    if ($ext == 'png' || $ext == 'jpg' || $ext == 'jpeg' || $ext == 'gif') {
                        $post['files'][] = $file['guid'];
                        $post['cover_media'] = $file['guid'];
                    } else {
                        $post['files'][] = $file['guid'];
                    }
                }

                $post['wp_post_data'] = serialize($post_wp);
                //$post=update_wp_post_data($post);

                $this->push_content($post);

                $i++;
                if ($max_records && $i >= $max_records) {
                    break;
                }
            }
        }
    }

    public function get_unique_user_id() {
        $sql = new MySQL();
        $bytes = strtoupper(bin2hex(random_bytes(3)));

        $q = $sql->executeSQL("SELECT `id` FROM `data` WHERE `content`->'$.user_id'='$bytes' && `content`->'$.type'='user'");
        if ($q[0]['id']) {
            return $this->get_unique_user_id();
        } else {
            return $bytes;
        }
    }

    public function do_shell_command($cmd) {
        ob_start();
        passthru($cmd);
        $tml = ob_get_contents();
        ob_end_clean();
        return $tml;
    }

    public function get_upload_dir_path() {
        return ABSOLUTE_PATH . '/uploads/' . date('Y') . '/' . date('m-F') . '/' . date('d-D');
    }

    public function get_upload_dir_url() {
        return BASE_URL . '/uploads/' . date('Y') . '/' . date('m-F') . '/' . date('d-D');
    }

    public function get_uploader_path() {
        $folder_path = 'uploads/' . date('Y') . '/' . date('m-F') . '/' . date('d-D');
        if (!is_dir(ABSOLUTE_PATH . '/' . $folder_path)) {
            mkdir(ABSOLUTE_PATH . '/' . $folder_path, 0755, true);
        }

        return array('upload_dir' => ABSOLUTE_PATH . '/' . $folder_path, 'upload_url' => BASE_URL . '/' . $folder_path);
    }

    public function get_uploaded_file_versions($file_url, $thumbnail = 'xs') {
        $file_arr = array();
        $file_parts = explode('/', $file_url);
        $file_parts = array_reverse($file_parts);
        $filename = urldecode($file_parts[0]);
        if (strlen($file_parts[1]) == 2) {
            $year = $file_parts[4];
            $month = $file_parts[3];
            $day = $file_parts[2];
            $size = $file_parts[1];
        } else {
            $year = $file_parts[3];
            $month = $file_parts[2];
            $day = $file_parts[1];
        }
        $sizes = array('xl', 'lg', 'md', 'sm', 'xs');
        foreach ($sizes as $size) {
            if (file_exists(ABSOLUTE_PATH . '/uploads/' . $year . '/' . $month . '/' . $day . '/' . $size . '/' . $filename)) {
                $file_arr['path'][$size] = ABSOLUTE_PATH . '/uploads/' . $year . '/' . $month . '/' . $day . '/' . $size . '/' . $filename;
                $file_arr['url'][$size] = BASE_URL . '/uploads/' . $year . '/' . $month . '/' . $day . '/' . $size . '/' . urlencode($filename);
            }
        }
        $file_arr['path']['source'] = ABSOLUTE_PATH . '/uploads/' . $year . '/' . $month . '/' . $day . '/' . $filename;
        $file_arr['url']['source'] = $file_url;

        if (preg_match('/\.(gif|jpe?g|png)$/i', $file_arr['path']['source'])) {
            if (file_exists($file_arr['path'][$thumbnail])) {
                $file_arr['url']['thumbnail'] = $file_arr['url'][$thumbnail];
                $file_arr['path']['thumbnail'] = $file_arr['path'][$thumbnail];
            } else {
                $file_arr['url']['thumbnail'] = $file_arr['url']['source'];
                $file_arr['path']['thumbnail'] = $file_arr['path']['source'];
            }
        } else {
            $file_arr['url']['thumbnail'] = '';
        }

        return $file_arr;
    }

    public function get_dir_url() {
        return str_replace(ABSOLUTE_PATH, BASE_URL, getcwd());
    }

    public function do_upload_file_from_url($url) {
        if ($url ?? false) {
            $path = $this->get_uploader_path();

            $file_name = time() . '-' . basename($url);
            $wf_uploads_path = $path['upload_dir'] . '/' . $file_name;
            $wf_uploads_url = $path['upload_url'] . '/' . $file_name;

            if (copy($url, $wf_uploads_path)) {
                return $wf_uploads_url;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
}
