<?php
header('Content-Type: application/vnd.api+json');

$accessArray = $auth->getApiAccess($userpass[0], $userpass[1]);

//If user has access, pass bearer token
if ($accessArray) {
    echo json_encode($accessArray);
}

//Access denied
else {
    $api->sendResponse(401);
}

exit();
?>