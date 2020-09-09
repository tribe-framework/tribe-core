<?php
namespace WildFire;

class API
{
    /**
     * returns decoded json from post body
     */
    public static function getRequest () {
        $json = file_get_contents('php://input');
        $data = json_decode($json);
        return $data;
    }

    public function print () {
        echo 'hello';
    }

    /**
     * function hits ip-api to get location details
     */
    public static function getLocation ($ip = NULL) {
        $dash = new Dash();
        if (!$ip) {
            return false;
        }

        $url = 'http://ip-api.com/json/' . $ip;
        $cmd = 'curl -X GET ' . $url;

        $res = $dash->do_shell_command($cmd);
        return json_decode($res);
    }

    /**
     * this function can send a json response setting appropriate headers
     */
    public static function json ($res) {
        header('Content-Type: application/json');
        echo json_encode($res);
    }
}
?>
