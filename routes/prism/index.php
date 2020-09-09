<?php
use WildFire\API;

$sql = new WildFire\MySQL();
$trac = new WildFire\Trac();

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    WildFire\PageError::notFound();
}

/**
 * Server to post request
 */
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    handlePost();
}

function handlePost () {
    unset($_SERVER['SERVER_SIGNATURE']);
    $body = API::getRequest();
    $prism_visit_id;

    if (!isset($body->prism_visit_id)) {
        $prism_visit_id = $trac->push_visit(array_merge($_SERVER, (array) $body));
    } elseif (($body->action ?? '') == 'click') {
        $trac->push_visit_meta($prism_visit_id, 'click_'.time(), json_encode($body));
    } elseif (isset($body->unload)) {
        $trac->push_visit_meta($prism_visit_id, 'time_spent', $body->time_spent);
    }

    API::json(array('prism_visit_id'=>$prism_visit_id ?? $body->prism_visit_id));
}
?>
