<?php
namespace WildFire;

class PostBody
{
    /**
     * returns decoded json from post body
     */
    public static function getPost () {
        $json = file_get_contents('php://input');
        $data = json_decode($json);
        return $data;
    }

    public function print () {
        echo 'hello';
    }
}
?>
