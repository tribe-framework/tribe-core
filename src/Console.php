<?php

namespace Wildfire\Core;

class Console {
    // pretty print raw data
    public static function debug($data, bool $halt = false)
    {
        $console = new \Wildfire\Core\Console;
        echo $console->prettyPrint(print_r($data, 1));

        if ($halt) {
            die();
        }
    }

    // pretty print json for debugging
    public static function json($data, bool $halt = false)
    {
        $console = new \Wildfire\Core\Console;
        echo $console->prettyPrint(json_encode($data, JSON_PRETTY_PRINT));

        if ($halt) {
            die();
        }
    }

    // used to display custom deprecation notice
    public static function deprecate($msg) {
        if ('dev' == strtolower($_ENV['ENV'])) {
            ob_start();
            echo "<div style='border: 1px solid #000; padding: 0 1rem;'>";
            trigger_error($msg, E_USER_DEPRECATED);
            echo "<br/></div>";
            echo "<b style='margin:1rem 0 0.5rem 0; display:inline-block'>Stack Trace:</b>";
            echo "<div style='border: 1px solid #000; padding:1rem 1.5rem; background-color:beige'>";
            debug_print_backtrace();
            echo "</div>";
            $data = ob_get_clean();
            self::debug($data);
        }
    }

    private function prettyPrint($message) {
        return '<div
            style="
                background-color:#f3f3f3;
                padding: 0.5rem 0.8rem;
                border-radius: 0;
                box-shadow: 0 0 0 4px #f3f3f3, inset 0 0 0 1px #000;
                margin: 1rem 0;
            "
            ><pre
                style="
                    white-space:pre-wrap;
                    color:#000;
                    font-family: sans-serif;
                    font-size: 0.9rem;
                "
                >'.$message.
            '</pre>
        </div>';
    }
}
