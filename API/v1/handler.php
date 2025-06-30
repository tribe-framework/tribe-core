<?php
/*
 * This file is supposed to be called from \Wildfire\Api and thus depends on
 * properties and functions of class Api. Please reference the Api class for
 * more clarity on this file's behavior
 */
use alsvanzelf\jsonapi\ErrorsDocument;
use alsvanzelf\jsonapi\ResourceDocument;
use alsvanzelf\jsonapi\CollectionDocument;
use alsvanzelf\jsonapi\objects\ResourceObject;
use Wildfire\Core\Dash as Dash;
use \Wildfire\Core\MySQL as MySQL;

// route requests based on method
switch (strtolower($_SERVER['REQUEST_METHOD'])) {
    case 'get':
        fetch($this, $url_parts, $all_types);
        break;

    case 'post':
        if ($url_parts[0] == 'file-upload') {
            upload($this);
        } else {
            create($this, $url_parts, $all_types);
        }
        break;

    case 'put':
        update($this, $url_parts, $all_types);
        break;

    case 'delete':
        delete($this, $url_parts, $all_types);
        break;

    default:
        $this->json(['error' => 'request method not allowed'])->send(403);
        break;
}

function fetch(\Wildfire\Api $api, array $url_parts, array $all_types): void
{
    try {
        $dash = new Dash;
        $sql = new MySQL;
        $check_use_id = false;  // default value will be overriden if required

        if (is_numeric($url_parts[0])) {    // if request has only numeric id
            $check_use_id = true;
            $use_id = true;
        } else if ($url_parts[1] && in_array($url_parts[0], $all_types)) {  // valid type and slug
            $check_use_id = true;
            $use_id = false;
        }

        $options = [
            'prettyPrint' => true,
            'encodeOptions' =>  JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PARTIAL_OUTPUT_ON_ERROR
        ];

        if ($check_use_id) {
            if ($use_id) {
                $res = $sql->get($url_parts[0]);
            } else {
                $res = $dash->get_content(['type' => $url_parts[0], 'slug' => $url_parts[1]]);
            }

            if (!$res) {    // send 404 message
                throw new Exception('unknown id', 404);
            }

            $doc = new ResourceDocument($type=$res['type'], $id=$res['id']);
            unset($res['type'], $res['id']);

            if (isset($_GET['filter'])) {
                $filter = explode(',', $_GET['filter']);

                foreach($filter as $f) {
                    $doc->add($f, $res[$f]);
                }
            } else {
                foreach($res as $key => $value) {
                    $doc->add($key, $value);
                }
            }

            $doc->sendResponse($options);
            return;
        }

        /**
         * $url_parts[0]: valid
         * type: defined & valid
         * slug: undefined
         */
        if (!$url_parts[1] && in_array($url_parts[0], $all_types)) {
            $db_index = $_GET['index'] ?? 0;
            $db_limit = $_GET['limit'] ?? 20;

            $res = $dash->get_all_ids($url_parts[0], 'id', 'ASC', "$db_index,$db_limit");
            $res = $dash->get_content($res, true);

            if (!$res) {
                throw new Exception('unknown id', 404);
            }

            foreach($res as $r) {
                $temp = new ResourceObject($r['type'], $r['id']);

                if (isset($_GET['filter'])) {
                    $filters = explode(',', $_GET['filter']);

                    foreach($filters as $filter) {
                        $temp->add($filter, $r[$filter]);
                    }
                } else {
                    unset($r['id'], $r['type']);

                    foreach($r as $key => $value) {
                        $temp->add($key, $value);
                    }
                }

                $final_object[] = $temp;
            }

            $doc = CollectionDocument::fromResources(...$final_object);
            $doc->sendResponse($options);
            return;
        }

        // default error
        throw new Exception('unknown request', 404);
    } catch (Exception $e) {
        $options = [
            'includeExceptionTrace'    => false,
            'includeExceptionPrevious' => false,
            'prettyPrint' => true,
        ];
        $document = ErrorsDocument::fromException($e, $options);

        $document->setHttpStatusCode($e->getCode());
        $document->sendResponse();
        return;
    }
}

function create(\Wildfire\Api $api, array $url_parts, array $all_types): void
{
    $dash = new Wildfire\Core\Dash;

    $type = $url_parts[0] ?? null;

    if (!($type || in_array($type, $all_types))) {
        $api->json([ 'error' => 'not found' ])->send(404);
    }

    $req = $api->body();
    $type_arr = [ "type" => $type ];

    if ($type == 'user') {
        if ($req['password'] !== $req['confirm_password']) {
            $api->json(['error' => 'passwords do not match'])->send(401);
        }

        $type_arr['user_id'] = $dash->get_unique_user_id();
        unset($req['confirm_password']);
    }

    $req = array_merge($req, $type_arr);
    $res = $dash->push_content($req);

    $api->json($res)->send();
}

function update(\Wildfire\Api $api, array $url_parts, array $all_types): void
{
    $dash = new \Wildfire\Core\Dash;

    if (!is_numeric($url_parts[0]) && !$url_parts[1]) {
        $api->json(['error' => 'slug not mentioned'])->send(400);
    }

    if (is_string($url_parts[0]) && $url_parts[1]) {
        $id = (int)$dash->get_content_meta(array('type' => $url_parts[0], 'slug' => $url_parts[1]), 'id');
    }

    if (is_numeric($url_parts[0])) {
        $id = (int)$url_parts[0];
    }

    $req = $api->body();

    /**
     * ToDo: password hashing still needs to be handled
     */
    if ($req['password']) {
        if ($req['password'] !== $req['confirm_password']) {
            $api->json(['error' => "passwords don't match"])->send(400);
        }
    }

    foreach ($req as $key => $value) {
        if (gettype($value) == 'array') {
            $value = json_encode($value);
        }

        $status = $dash->push_content_meta($id, $key, $value);

        if (!$status) {
            $api->json(['error' => 'something went wrong'])->send(500);
        }
    }

    $res = $dash->get_content($id);

    $api->json($res)->send();
}

function delete(\Wildfire\Api $api, array $url_parts, array $all_types): void
{
    $dash = new Wildfire\Core\Dash;

    if (is_numeric($url_parts[0])) {
        $id = (int)$url_parts[0];

        $res_0 = $dash->get_content($id);

        if (!$res_0) {
            $api->json(['error' => 'id invalid'])->send(404);
        }

        $dash->do_delete(['id' => $id]);
        $res_1 = $dash->get_content($id);

        if ($res_0 && !$res_1) {
            $api->json(['success' => true])->send();
        }

        $api->json(['error' => 'something went wrong'])->send(503);
    }

    if (!$url_parts[1] || !in_array($url_parts[0], $all_types)) {
        $api->json(['error' => 'invalid request'])->send(400);
    }

    $res = $dash->get_content(['type' => $url_parts[0], 'slug' => $url_parts[1] ]);

    if (!$res) {
        $api->json(['error' => 'record not found'])->send(404);
    }

    $dash->do_delete([
        'id' => $res['id']
    ]);

    $api->json(['success' => 'true'])->send();
}

function upload(\Wildfire\Api $api): void
{
    if (!$_FILES) {
        $api->json(['error' => 'no files uploaded'])->send(403);
    }

    $dash = new \Wildfire\Core\Dash;
    $uploads_dir = $dash->get_upload_dir_path();
    $uploads_base_url = $dash->get_upload_dir_url();

    if (!file_exists($uploads_dir)) {
        mkdir($uploads_dir, 0755, true);
    }

    foreach($_FILES['files']['error'] as $key => $error) {
        if ($error != UPLOAD_ERR_OK) {
            continue;
        }

        $tmp_name = $_FILES['files']['tmp_name'][$key];

        // basename() may prevent filesystem traversal attacks;
        $filename = basename($_FILES['files']['name'][$key]);

        // validating mime type for file
        $mime_type = mime_content_type($tmp_name);
        $mime_type = str_replace('/', '.', $mime_type); // replace '/' with period
        $valid_mime =  (bool) preg_match(UPLOAD_FILE_TYPES, $mime_type);

        if (!$dash->checkFileUploadName($filename)) {
            $api->json([ 'error' => 'filename not allowed' ])->send(403);
        }

        if (!$valid_mime) {
            $api->json(['error' => 'mime type not allowed'])->send(403);
        }

        $loc = "{$uploads_dir}/{$filename}";
        if (file_exists($loc)) {
            $suffix = 0;
            list($name, $ext) = explode('.', $filename);

            while(file_exists($loc)) {
                $suffix++;
                $filename = "{$name}-{$suffix}.${ext}";
                $loc = "{$uploads_dir}/{$filename}";
            }
        }

        move_uploaded_file($tmp_name, $loc);
        $tribe_root = TRIBE_ROOT;
        $upload_path = preg_replace("/.*(?=\/uploads)/", "", $loc);

        $res[] = [
            'name' => $filename,
            'type' => $_FILES['files']['type'][$key],
            'path' => $upload_path,
            'url' => "$uploads_base_url/$filename"
        ];
    } // foreach

    $api->json($res)->send();
}
