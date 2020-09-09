<?php
namespace WildFire;

class PageError
{
    public static function notFound ()
    {
        include_once '../routes/errors/404.php';
        die();
    }

    public static function forbidden ()
    {
        include_once '../routes/errors/403.php';
        die();
    }
}
?>
