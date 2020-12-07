<?php

namespace Wildfire\Core;

class Request {
    public static function postBody () {
        $json = file_get_contents('php://input');
        return json_decode($json);
    }
}
