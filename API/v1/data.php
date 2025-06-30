<?php
header('Content-Type: application/vnd.api+json');

$jsonOutput = array();

//Enter only if user has access
if ($currentUser['id']) {

    /*
    A document MUST contain at least one of the following top-level members:

    data: the document’s “primary data”
    errors: an array of error objects
    meta: a meta object that contains non-standard meta-information. */

    $jsonOutput['data'] = array();
    $jsonOutput['error'] = array();
    $jsonOutput['meta'] = array();

    /*
    A document MAY contain any of these top-level members:

    jsonapi: an object describing the server’s implementation
    links: a links object related to the primary data.
    included: an array of resource objects that are related to the primary data and/or each other (“included resources”). */

    $jsonOutput['jsonapi'] = array(
        'version' => '1.0',
        'whoami' => $currentUser['user_id'],
        'readmore' => 'https://github.com/wil-ldf-ire/tribe');
    $jsonOutput['links'] = array();

    /*
    Content Type is in $type
    Slug is saved in $slug
    ID of the content object is in $id
    Get parameters are in $_GET array
    Post parameters are in $_POST array */

    $type = $thisUriArray[3];
    if ($type != 'search' && ($thisUriArray[4] ?? false)) {

        if (is_numeric($thisUriArray[4])) {
            $id = $thisUriArray[4];
            $slug = $dash->get_content_meta($thisUriArray[4], 'slug');
        } else if ($slug = ($thisUriArray[4] ?? false)) {
            $slug = $thisUriArray[4];
            $id = $dash->get_content_meta(array('type' => $type, 'slug' => $slug), 'id');
        }

        //if there's a URI element after slug, it is an attribute key
        $attr_key = ($thisUriArray[5] ?? false);
    }

    $method = $_SERVER['REQUEST_METHOD'];

    switch ($method) {
    case 'PUT':
        //do_something_with_put($request);
        break;
    case 'POST':
        //do_something_with_put($request);
        break;

    case 'GET':
        //Get the attr/meta value with it's parent slug linked object
        if (($type ?? false) && $type != 'search') {

            if ($attr_key) {
                $postdata = $dash->get_content($id);
                $jsonOutput['data'][0]['type'] = $type . '-module';
                $jsonOutput['data'][0]['id'] = $id . '-' . $attr_key;
                $jsonOutput['data'][0]['attributes']['value'] = $postdata[$attr_key];
                $jsonOutput['included'][0] = $postdata;
            } else if ($slug) {
                $postdata = $dash->get_content($id);
                $jsonOutput['data'][0]['type'] = $type;
                $jsonOutput['data'][0]['id'] = $id;
                $jsonOutput['data'][0]['attributes'] = $postdata;
                $jsonOutput['data'][0]['links']['self'] = $_ENV['BASE_URL'] . '/' . $type . '/' . $slug;
                if ($postdata['user_id']) {
                    $jsonOutput['included'][0] = $auth->getUser($postdata['user_id']);
                    $jsonOutput['included'][1] = $types[$type];
                }
            } else {
                $types[$type]['id'] = 'type-' . array_search($type, array_keys($types));
                $jsonOutput['data'][0]['id'] = $types[$type]['id'];
                $jsonOutput['data'][0]['type'] = 'types.json';
                $jsonOutput['data'][0]['attributes'] = $types[$type];
                $jsonOutput['data'][0]['links']['self'] = $_ENV['BASE_URL'] . '/' . $type;
            }
        }

        break;

    default:
        //handle_error($request);
        break;
    }

    /*
    The members data and errors MUST NOT coexist in the same document. */
    if (empty($jsonOutput['error'])) {
        unset($jsonOutput['error']);
    } else {
        unset($jsonOutput['data']);
        unset($jsonOutput['meta']);
    }

    /*
    Print output */
    echo json_encode($jsonOutput);
}

//Access denied
else {
    $api->sendResponse(401);
}

exit();
?>