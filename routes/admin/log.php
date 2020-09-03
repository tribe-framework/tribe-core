<?php
include_once ('../init.php');
include_once (ABSOLUTE_PATH.'/admin/header.php');
var_dump($dash->do_shell_command('tail -200 /var/log/apache2/error.log'));
include_once (ABSOLUTE_PATH.'/admin/footer.php'); ?>