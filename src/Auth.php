<?php
namespace Tribe;

use \Tribe\Core;
use \Tribe\Config;
use \Tribe\MySQL;
use \Firebase\JWT\JWT;

class Auth
{
    protected static $types;
    public $cookie_options;

    public function __construct()
    {
        $config = new Config();
        self::$types = $config->getTypes();
        $_secure = ($_ENV['SSL'] == 'true');

        $this->cookie_options = [
            'expires' => 'Session',
            'path' => '/',
            'secure' => $_secure,
            'httponly' => true,
            'samesite' => 'Lax'
        ];
    }

    public function getUniqueUserID()
    {
        $sql = new MySQL();
        $bytes = strtoupper(bin2hex(random_bytes(3)));

        $q = $sql->executeSQL("SELECT id FROM data WHERE user_id='$bytes' ORDER BY id DESC LIMIT 0,1");

        if ($q && $q[0]['id']) {
            return $this->getUniqueUserID();
        } else {
            return $bytes;
        }
    }

    public function doAfterLogin($user, $redirect_url = '', $remember = false, $do_not_redirect = false)
    {
        global $_SESSION;

        $roleslug = $user['role_slug'];
        $types = self::$types;
        $user['role'] = $types['user']['roles'][$roleslug]['role'];

        //for admin and crew (staff)
        if ($user['role'] == 'admin' || $user['role'] == 'crew') {
            $user['junction_access'] = 1;
            $token = $this->setCurrentUser($user);

            $_redirect = 'Location: ' . (trim($redirect_url) ?: '/admin');
        }

        //for members
        elseif ($user['role'] == 'member') {
            $user['junction_access'] = 0;
            $token = $this->setCurrentUser($user);

            $_redirect = 'Location: ' . (trim($redirect_url) ?: '/user');
        }

        //for visitors and anybody else
        else {
            $_redirect = 'Location: ' . (trim($redirect_url) ?: '/');
        }

        $access_token = "{$token['token_type']} {$token['access_token']}";

        // setting http cookie
        $cookie_options = $this->cookie_options;
        if ($remember) {
            $cookie_options['expires'] = strtotime('+45 days');
            setcookie('access_token', $access_token, $cookie_options);
        } else {
            setcookie('access_token', $access_token, $cookie_options);
        }

        ob_start();

        if ($do_not_redirect !== true)
            header($_redirect);
        
        ob_end_flush();
    }

    public function getApiAccess($api_key, $api_secret)
    {
        $sql = new MySQL();
        $types = self::$types;

        $user_id = (string) json_decode($sql->executeSQL("
            SELECT `user_id` FROM `data`
            WHERE
            `content`->'$.api_key'='" . $api_key . "' &&
            `content`->'$.api_secret'='" . $api_secret . "' &&
            `type`='api_key_secret' LIMIT 0,1")[0]['user_id'], true);

        $user = $this->getUser($user_id);
        $roleslug = $user['role_slug'];
        $user['role'] = $types['user']['roles'][$roleslug]['role'];

        //for admin and dev (staff)
        if ($types['user']['roles'][$roleslug]['role'] == 'admin' || $types['user']['roles'][$roleslug]['role'] == 'dev') {
            return $this->setCurrentUser($user, 900);
        } else {
            return false;
        }
    }

    public function getUser($val)
    {
        $sql = new MySQL();
        $or = array();

        if (is_int($val)) {
            $q = $sql->executeSQL("SELECT * FROM `data` WHERE `id`='$val' && `type`='user' ORDER BY `id` DESC LIMIT 0,1");
        } else {
            $q = $sql->executeSQL("SELECT * FROM `data` WHERE `user_id`='$val' && `type`='user' ORDER BY `id` DESC LIMIT 0,1");
        }

        if ($q[0]['id']) {
            $or = json_decode($q[0]['content'], true);
            $or['id'] = $q[0]['id'];
            $or['updated_on'] = $q[0]['updated_on'];
            $or['created_on'] = $q[0]['created_on'];
            return $or;
        } else {
            return 0;
        }
    }

    public function getCurrentUser($access_token = '')
    {
        global $_SESSION, $_ENV;

        $token = $_COOKIE['access_token'] ?? '';
        $token = trim($token);
        if (!$token) {
            return false;
        }

        $token = str_replace('Bearer ', '', $token);

        try {
            $_jwt_secret = ($_ENV['TRIBE_API_SECRET_KEY'] ?? $_ENV['DB_PASS']).($_SESSION['user_id'] ?? '');
            $decoded = (array) JWT::decode($token, $_jwt_secret, ['HS256']);

            if (isset($_SESSION['user_id'])) {
                return ($_SESSION['user_id'] == $decoded['user_id']) ? $_SESSION : false;
            } else {
                $core = new Core;
                $user = $core->getObject(['type' => 'user', 'slug' => strtolower($decoded['user_id'])]);
                $_SESSION = $user;

                return $_SESSION;
            }
        } catch (\Exception $e) {
            return ($e->getMessage() == "Expired token") ? 'expired' : false;
        }
    }

    public function setCurrentUser($user, $timeout = 0)
    {
        global $_SESSION, $_ENV;

        $payload = array(
            "iss" => $_ENV['BASE_URL'] ?? $_ENV['WEB_URL'] ?? null, //“iss” (Issuer) Claim
            "aud" => $_ENV['BASE_URL'] ?? $_ENV['WEB_URL'] ?? null,
            "iat" => time(), //"iat" (Issued At) Claim
            "nbf" => time(), //"nbf" (Not Before) Claim
        );

        $_user = [
            "name" => $user['name'] ?? null,
            "user_id" => $user['user_id']
        ];

        // "exp" (Expiration Time) Claim
        if ($timeout) {
            $payload["exp"] = time() + $timeout;
        }

        $payload = array_merge($_user, $payload);
        unset($_user);

        $jwt_secret = ($_ENV['TRIBE_API_SECRET_KEY'] ?? $_ENV['DB_PASS']).$user['user_id'];
        $jwt_token = JWT::encode($payload, $jwt_secret);

        $_SESSION = $user;

        return array(
            "access_token" => $jwt_token,
            "token_type" => "Bearer",
            "user_id" => $user['user_id'],
        );
    }

    public function getUserId(array $post)
    {
        $sql = new MySQL();

        $_user = false;
        if ($post['email'] ?? false) {
            $_user = $sql->executeSQL(
                "SELECT id,content->>'$.password' as 'password'
                FROM data
                WHERE type='user' AND content->'$.email'='{$post['email']}'
                ORDER BY id ASC LIMIT 1"
            );
        } else if ($post['mobile'] ?? false) {
            $_user = $sql->executeSQL(
                "SELECT id,content->>'$.password' as 'password'
                FROM data
                WHERE type = 'user' AND content->'$.mobile' = '{$post['mobile']}'
                ORDER BY id ASC LIMIT 1"
            );
        } else if ($post['uname'] ?? false) {
            $_user = $sql->executeSQL(
                "SELECT id,content->>'$.password' as 'password'
                FROM data
                WHERE type = 'user' AND content-'$.uname' = '{$post['uname']}'
                ORDER BY id ASC LIMIT 1"
            );
        }

        if (!$_user) {
            return false;
        }

        $_user = $_user[0];

        $is_valid = $this->verify_password($post['password'], $_user['password']);

        return $is_valid ? $_user['id'] : ['ok' => false];
    }

    public function endSession()
    {
        $cookie_options = $this->cookie_options;
        session_destroy();

        return setcookie('access_token', '', $cookie_options);
    }

    public function secure_password(string $password)
    {
        return \password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    public function verify_password(string $password, string $hash): bool
    {
        $is_valid = \password_verify($password, $hash);

        if (!$is_valid) {
            $is_valid = md5($password) == $hash;
        }

        return $is_valid;
    }
}
