<?php

namespace Wildfire\Core;

class Console {
    public static function debug($data, bool $halt = false)
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
                >'.print_r($data, 1).
            '</pre>
        </div>';

        if ($halt) {
            die();
        }
    }

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
}
