<?php

$auth = new \Wildfire\Auth;
$auth->endSession();

header("Location: /user/login");
