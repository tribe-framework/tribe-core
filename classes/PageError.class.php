<?php
class PageError {
    function pageNotFound () {
        if (file_exists(THEME_PATH.'/404.php')) {
            include_once THEME_PATH.'/404.php';
        }
    }
}
?>
