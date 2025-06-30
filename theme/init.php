<?php
namespace Wildfire;

$sql = new Core\MySQL();
$dash = new Core\Dash();
$admin = new Admin;
$auth = new Auth;

$type = 'user';
$types = $dash->getTypes();
$menus = $dash->getMenus();
$currentUser = $auth->getCurrentUser();
