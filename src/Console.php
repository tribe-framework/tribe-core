<?php

namespace Wildfire\Core;

class Console {
    // pretty print raw data
    public static function debug($data, bool $halt = false)
    {
        echo '<div
            style="
                background-color:#f3f3f3;
                padding: 0.5rem 1rem;
                border-radius: 0;
                box-shadow: 0 0 0 4px #f3f3f3, inset 0 0 0 2px #000;
                margin: 1rem 0;
            "
            ><pre
                style="
                    white-space:pre-wrap;
                    color:#000;
                    font-family: sans-serif;
                    font-size: 0.9rem;
                "
                >'.print_r($data, 1).
            '</pre>
        </div>';

        if ($halt) {
            die();
        }
    }

    // pretty print json for debugging
    public static function json($data, bool $halt = false)
    {
        echo '<div
            class="container my-2"
            style="
                background-color:black;
                padding: 0.5rem 1rem;
                border-radius: 8px;
                box-shadow: 0 0 0 4px #000, inset 0 0 0 2px purple;
            "
            ><pre
                class="small col-md-10 mx-auto text-white"
                style="
                    white-space:pre-wrap;
                    color:#fff;
                "
                >'.json_encode($data, JSON_PRETTY_PRINT).
            '</pre>
        </div>';
    }

    // used to display custom deprecation notice
    public static function deprecate($msg) {
        if ('dev' == strtolower($_ENV['ENV'])) {
            ob_start();
            trigger_error($msg, E_USER_DEPRECATED);
            debug_print_backtrace();
            $data = ob_get_clean();
            self::debug($data);
        } else {
            trigger_error($msg, E_USER_DEPRECATED);
        }
    }
}
