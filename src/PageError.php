<?php
namespace WildFire;

class PageError
{
    public function notFound ()
    {
        include_once '../routes/errors/404.php';
    }

    public function forbidden ()
    {
        include_once '../routes/errors/403.php';
    }
}
?>
