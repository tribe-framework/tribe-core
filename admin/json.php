<?php
include_once ('../config-init.php');
${$_POST['class']}->{$_POST['function']}($_POST);
echo json_encode(array('last_error'=>${$_POST['class']}->get_last_error(), 'last_info'=>${$_POST['class']}->get_last_info(), 'last_data'=>${$_POST['class']}->get_last_data(), 'last_redirect'=>${$_POST['class']}->get_last_redirect()));
?>