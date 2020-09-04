<?php
class PageError {
    function notFound () {
        include_once '../routes/errors/404.php';
    }

    function forbidden () {
        include_once '../routes/errors/403.php';
    }
}
?>
