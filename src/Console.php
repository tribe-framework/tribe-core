<?php

namespace Wildfire\Core;

class Console
{
    public static function log($data, $halt = false)
    {
        echo '<pre>'.print_r($data, 1).'</pre>';

        if ($halt) {
            die();
        }
    }
}
