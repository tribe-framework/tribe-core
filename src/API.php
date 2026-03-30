<?php
namespace Tribe;

use \JetBrains\PhpStorm\NoReturn;
use \alsvanzelf\jsonapi\CollectionDocument;
use \alsvanzelf\jsonapi\ResourceDocument;
use \alsvanzelf\jsonapi\MetaDocument;

class API {
    private $response;
    private $request;
    public $requestBody;
    private $allowed_read_access_api_keys = [];
    private $allowed_full_access_api_keys = [];
    private $api_objects = [];
    private $thisRequestHasApiAccess = false;
    private $url_parts;
    private $type;
    private $id;
    private $ids;
    private $idr;

    // private properties for Tribe objects
    private $config;
    private $core;
    private $sql;
    private $typesense;

    public function __construct()
    {
        $this->requestBody = \json_decode(\file_get_contents('php://input'), 1) ?? [];

        $this->config = new \Tribe\Config;
        $this->core = new \Tribe\Core;
        $this->sql = new \Tribe\MySQL;
        $this->typesense = new \Tribe\Typesense;

        $this->url_parts = explode('/', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
        $this->type = (string) ($this->url_parts[2] ?? '');

        $url_segment = $this->url_parts[3] ?? null;

        if ($url_segment && !is_numeric($url_segment)) {
            $this->id = (int) $this->core->getAttribute(array('type'=>$this->type, 'slug'=>$url_segment), 'id');
        } else {
            $this->id = (int) ($url_segment ?? 0);
        }

        // Load API keys
        $this->loadApiKeys();

        // Handle CORS for API requests
        $this->handleCors();
    }

    /**
     * Handle CORS headers for API requests
     */
    private function handleCors() {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_HOST'] ?? '*';
        $request_method = $_SERVER['REQUEST_METHOD'];

        header("Access-Control-Allow-Origin: $origin");
        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Max-Age: 86400");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-API-KEY, X-Requested-With, Accept, Origin");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS");

        if ($request_method === 'OPTIONS') {
            http_response_code(200);
            exit(0);
        }

        return;
    }

    /**
     * Load API keys from the database
     */
    private function loadApiKeys()
    {
        $this->allowed_read_access_api_keys = $this->allowed_full_access_api_keys = [];
        $this->api_objects = [];
        $api_ids = $this->core->getIDs(array('type'=>'apikey_record'), "0, 25", 'id', 'DESC', false);

        if (!$api_ids) {
            return;
        }

        $api_objects = $this->core->getObjects($api_ids);

        foreach ($api_objects as $api_object) {
            $this->api_objects[$api_object['apikey']] = $api_object;

            $is_privacy_valid = in_array($api_object['content_privacy'], ['public', 'private'], true);

            if ($is_privacy_valid) {
                if (empty($api_object['readonly'])) {
                    $this->allowed_full_access_api_keys[] = $api_object['apikey'];
                } else {
                    $this->allowed_read_access_api_keys[] = $api_object['apikey'];
                }
            }
        }
    }

    /**
     * Public accessor for API key validation
     */
    public function validateApiKeyPublic(): bool
    {
        return $this->validateApiKey();
    }

    /**
     * Validates API key authentication and handles exceptions
     */
    private function validateApiKey()
    {
        global $_ENV;

        $request_api_key = null;
        $auth_header = $_SERVER['HTTP_AUTHORIZATION'] ?? null;
        if ($auth_header && strpos($auth_header, 'Bearer ') === 0) {
            $request_api_key = str_replace('Bearer', '', $auth_header);
            $request_api_key = trim($request_api_key);
        }

        $request_domain = parse_url((isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : ''), PHP_URL_HOST);
        if (empty($request_domain)) {
            $request_domain = $_SERVER['HTTP_HOST'] ?? '';
        }

        $instance_slug = (explode('.', $request_domain)[0]) ?? '';

        $is_tribe_domain = false;
        if (!empty($request_domain)) {
            $tribe_domains = [
                $_ENV['BARE_URL']
            ];

            foreach ($tribe_domains as $domain) {
                if (strpos($request_domain, $domain) !== false) {
                    $is_tribe_domain = true;
                    break;
                }
            }
        }

        $is_localhost = in_array($request_domain, ['localhost', '127.0.0.1']) ||
                        strpos($request_domain, 'localhost:') === 0 ||
                        strpos($request_domain, '127.0.0.1:') === 0;

        $request_method = strtoupper($_SERVER['REQUEST_METHOD']);

        $typesJSON = $this->config->getTypes();
        $block_read_access_without_apikey = isset($typesJSON['block_read_access_without_apikey']) && $typesJSON['block_read_access_without_apikey'];

        $allow_all = filter_var($_ENV['ALLOW_ALL_CONNECTIONS_DANGEROUSLY'] ?? false, FILTER_VALIDATE_BOOLEAN);
        if ($allow_all) {
            return true;
        }

        if ($is_tribe_domain) {
            return true;
        }

        if ($request_api_key && isset($this->api_objects[$request_api_key])) {
            $api_object = $this->api_objects[$request_api_key];

            if (isset($api_object['devmode']) && $api_object['devmode'] === true && $is_localhost) {
                return true;
            }

            if (!empty($api_object['whitelisted_domains'])) {
                $whitelisted_domains = array_map('trim', explode("\n", $api_object['whitelisted_domains']));

                foreach ($whitelisted_domains as $domain_pattern) {
                    if (strpos($domain_pattern, '*') !== false) {
                        $pattern = '/^' . str_replace('*', '.*', preg_quote($domain_pattern, '/')) . '$/i';
                        if (preg_match($pattern, $request_domain)) {
                            if ($request_method === 'GET') {
                                return true;
                            } else if (in_array($request_method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
                                return in_array($request_api_key, $this->allowed_full_access_api_keys);
                            }
                        }
                    } else if (strtolower($domain_pattern) === strtolower($request_domain)) {
                        if ($request_method === 'GET') {
                            return true;
                        } else if (in_array($request_method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
                            return in_array($request_api_key, $this->allowed_full_access_api_keys);
                        }
                    }
                }
            }
        }

        $is_allowed = in_array($request_api_key, $this->allowed_read_access_api_keys);

        if (!$is_allowed) {
            $this->thisRequestHasApiAccess = false;
            $_GET['show_public_objects_only'] = true;
        } else {
            $this->thisRequestHasApiAccess = true;
        }

        if (!isset($request_method) || $request_method == 'GET' || $request_method == '') {
            if ($block_read_access_without_apikey || $this->type == 'webapp' || $this->type == 'apikey_record') {
                return $is_allowed;
            } else {
                return true;
            }
        } else if (in_array($request_method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return in_array($request_api_key, $this->allowed_full_access_api_keys);
        }

        return false;
    }

    /**
     * Process linked modules for an object and add relationships to the document
     */
    private function processLinkedModules(
        ResourceDocument $document,
        array $object,
        array $linked_modules,
        ?array $related_objects_core = null,
        ?array $rojt = null,
        ?array $id_rojt = null
    ) {
        foreach ($linked_modules as $module_key => $module_type) {
            if (array_key_exists($module_key, $object)) {
                $value = $object[$module_key];

                if (empty($value)) {
                    continue;
                }

                if ($related_objects_core === null) {
                    $related_objects = [];

                    if (is_array($value)) {
                        if (!empty($value) && is_numeric($value[0] ?? '')) {
                            $related_objects = $this->core->getObjects(implode(',', $value));
                        } else {
                            $query_params = [];
                            foreach ($value as $slug) {
                                if (!empty($slug)) {
                                    $query_params[] = [
                                        'type' => $module_type,
                                        'slug' => $slug
                                    ];
                                }
                            }
                            $related_objects = $this->core->getObjects($query_params);
                        }
                    } else if (is_string($value)) {
                        if (strpos($value, ',') !== false && is_numeric(trim(explode(',', $value)[0]))) {
                            $related_objects = $this->core->getObjects($value);
                        } else if (is_numeric($value)) {
                            $related_objects = $this->core->getObjects($value);
                        } else {
                            $obj = $this->core->getObject([
                                'type' => $module_type,
                                'slug' => $value
                            ]);
                            if ($obj) {
                                $related_objects = [$obj];
                            }
                        }
                    } else if (is_int($value)) {
                        $obj = $this->core->getObject($value);
                        if ($obj) {
                            $related_objects = [$obj];
                        }
                    }

                    if (!empty($related_objects)) {
                        $relationships = [];
                        $processed_ids = [];

                        foreach($related_objects as $related_object) {
                            if (empty($related_object) || !isset($related_object['id'])) {
                                continue;
                            }

                            $related_id = $related_object['id'];

                            if ($related_id == $object['id'] || isset($processed_ids[$related_id])) {
                                continue;
                            }

                            $processed_ids[$related_id] = true;

                            $ojt = new ResourceDocument($module_type, $related_id);
                            $ojt->add('modules', $related_object);
                            $ojt->add('slug', $related_object['slug'] ?? '');
                            $relationships[] = $ojt;
                        }

                        if (!empty($relationships)) {
                            $document->addRelationship($module_key, $relationships);
                        }
                    }
                } else {
                    $items_to_process = [];

                    if (is_array($value)) {
                        $items_to_process = $value;
                    } else if (is_string($value) && strpos($value, ',') !== false) {
                        $items_to_process = array_map('trim', explode(',', $value));
                    } else {
                        $items_to_process = [$value];
                    }

                    $relationships = [];
                    $processed_ids = [];

                    foreach ($items_to_process as $item) {
                        if (empty($item)) {
                            continue;
                        }

                        if (is_numeric($item)) {
                            $related_id = (int)$item;

                            if (isset($processed_ids[$related_id]) ||
                                $related_id == $object['id'] ||
                                !isset($id_rojt[$related_id])) {
                                continue;
                            }

                            $processed_ids[$related_id] = true;

                            $related_object = $id_rojt[$related_id];
                            $ojt = new ResourceDocument($module_type, $related_id);
                            $ojt->add('modules', $related_object);
                            $ojt->add('slug', $related_object['slug'] ?? '');
                            $relationships[] = $ojt;
                        } else if (isset($rojt[$module_type][$item])) {
                            $related_id = $rojt[$module_type][$item];

                            if (isset($processed_ids[$related_id]) ||
                                !$related_id ||
                                $related_id == $object['id'] ||
                                !isset($related_objects_core[$related_id])) {
                                continue;
                            }

                            $processed_ids[$related_id] = true;

                            $ojt = new ResourceDocument($module_type, $related_id);
                            $ojt->add('modules', $related_objects_core[$related_id]);
                            $ojt->add('slug', $item);
                            $relationships[] = $ojt;
                        }
                    }

                    if (!empty($relationships)) {
                        $document->addRelationship($module_key, $relationships);
                    }
                }
            }
        }

        return $document;
    }

    public function jsonAPI($version = '1.1') {
        if (!$this->validateApiKey()) {
            $error = [
                'errors' => [
                    array(
                        'status' => '403',
                        'title' => 'Forbidden',
                        'detail' => 'You do not have permission to access this resource. If your request is using an API key in production mode, make sure it is from a whitelisted domain. Use Junction to generate API keys and whitelist your domains.'
                    )
                ]
            ];
            $this->json($error)->send(403);
        }

        if ($version !== '1.1') {
            return;
        }

        // Handle search endpoint
        if ($this->type === 'search') {
            $this->handleSearchEndpoint();
            return;
        }

        // version 1.1
        $linked_modules = $this->config->getTypeLinkedModules($this->type);

        if ($this->method('DELETE')) {
            if (!$this->id) {
                $this->send(404);
            }

            $deleteType = $this->type;

            if ($this->core->deleteObject($this->id)) {
                try { $this->typesense->delete($deleteType, $this->id); } catch (\Throwable $e) { error_log('[Typesense] delete error: ' . $e->getMessage()); }
                $document = new ResourceDocument();
                $document->sendResponse();
                die();
            }
            else {
                $this->send(404);
            }
        }
        elseif ($this->method('PATCH')) {
            $object = $this->requestBody;

            if ($this->type == 'webapp') {

                $this->pushTypesObject($object);
                $this->getTypesObject();

            } else {

                $existingObject = $this->core->getObject($object['data']['id']);
                $oldUrl = $existingObject['url'] ?? null;

                $object = array_merge($existingObject, $object['data'], $object['data']['attributes']['modules']);
                unset($object['attributes']);

                $object = $this->core->getObject($this->core->pushObject($object));

                // Re-transcribe file_record when its URL changes
                $newUrl = $object['url'] ?? null;
                if ($oldUrl !== $newUrl) {
                    $object = $this->transcribeIfFileRecord($object);
                }

                try { $this->typesense->upsert($object); } catch (\Throwable $e) { error_log('[Typesense] upsert error: ' . $e->getMessage()); }

                $document = new ResourceDocument($this->type, $object['id']);
                $document->add('modules', $object);
                $document->add('slug', $object['slug']);

                if ($linked_modules != []) {
                    $document = $this->processLinkedModules($document, $object, $linked_modules);
                }

                $document->sendResponse();
            }
        }
        elseif ($this->method('POST')) {
            $object = $this->requestBody;

            if ($this->type == 'webapp') {

                $this->pushTypesObject($object);
                $this->getTypesObject();

            } else {

                $object = array_merge($object['data'], $object['data']['attributes']['modules']);
                unset($object['attributes']);

                if ($object['type'] == 'user')
                    $object['user_id'] = $this->core->getUniqueUserID();

                $object = $this->core->getObject($this->core->pushObject($object));

                // Transcribe file_record objects on creation
                $object = $this->transcribeIfFileRecord($object);

                try { $this->typesense->upsert($object); } catch (\Throwable $e) { error_log('[Typesense] upsert error: ' . $e->getMessage()); }

                $document = new ResourceDocument($this->type, $object['id']);
                $document->add('modules', $object);
                $document->add('slug', $object['slug']);

                if ($linked_modules != []) {
                    $document = $this->processLinkedModules($document, $object, $linked_modules);
                }

                $document->sendResponse();
            }
        }
        elseif ($this->method('GET')) {
            if ($this->type == 'webapp') {
                $this->getTypesObject();
            }
            else if (($this->type ?? false) && !($this->id ?? false)) {
                //PAGINATION
                $limit = "0, 25";
                $limitParam  = filter_var($_GET['page']['limit'] ?? null, FILTER_VALIDATE_INT, ['options' => ['default' => 25]]);
                $offsetParam = filter_var($_GET['page']['offset'] ?? null, FILTER_VALIDATE_INT, ['options' => ['default' => 0]]);

                if ($limitParam != '-1') {
                    if (!$offsetParam) {
                        $offsetParam = 0;
                    }

                    if (!$limitParam) {
                        $limitParam = 25;
                    }

                    if ($limitParam !== null && $offsetParam !== null) {
                        $limit = "$offsetParam, $limitParam";
                    } elseif ($limitParam !== null) {
                        $limit = $limitParam;
                    }
                } else {
                    $limit = "";
                }

                //SORTING
                if ($_GET['sort'] ?? false) {
                    if ($_GET['sort'] == '(random)') {
                        $sort_field = '(random)';
                        $sort_order = 'DESC';
                    }
                    else {
                        $sort_arr = array_map('trim', explode(',', $_GET['sort']));
                        $sort_field = $sort_order = array();

                        foreach ($sort_arr as $val) {
                            if (substr($val, 0, 1) == '-') {
                                $sort_field[] = substr($val, 1, strlen($val));
                                $sort_order[] = 'DESC';
                            }
                            else {
                                $sort_field[] = $val;
                                $sort_order[] = 'ASC';
                            }
                        }
                    }
                }
                else {
                    $sort_field = 'id';
                    $sort_order = 'DESC';
                }

                //getting IDs
                if ($_GET['sql']) {
                    $this->ids = $this->sql->executeSQL($_GET['sql']);
                }
                else {
                    $this->ids = $this->core->getIDs(
                        $search_array = array_merge(
                            ($_GET['filter'] ?? []),
                            ($_GET['modules'] ?? []),
                            array('type'=>$this->type)
                        ),
                        $limit,
                        $sort_field,
                        $sort_order,
                        $show_public_objects_only = (($_GET['show_public_objects_only'] === 'false' || $_GET['show_public_objects_only'] === false) ? boolval(false) : boolval(true)),
                        $ignore_ids = ($_GET['ignore_ids'] ?? []),
                        $show_partial_search_results = (($_GET['filter'] ?? false) ? boolval(true) : boolval(false)),
                        false, 'LIKE', 'OR', 'AND', ($_GET['range'] ?? [])
                    );
                }

                if ($this->ids)
                {
                    $objectr = $this->core->getObjects($this->ids);
                    $objects = [];

                    foreach ($this->ids as $this->idr) {
                        $objects[] = $objectr[$this->idr['id']];
                    }

                    $i = 0;
                    $related_objects_meta = [];
                    $related_objects_core = [];
                    $rojt = [];
                    foreach ($objects as $object) {
                        $documents[$i] = new ResourceDocument($this->type, $object['id']);
                        $documents[$i]->add('modules', $object);
                        $documents[$i]->add('slug', $object['slug']);

                        if ($linked_modules != []) {
                            foreach ($linked_modules as $module_key => $module_type) {
                                if (array_key_exists($module_key, $object)) {
                                    $value = $object[$module_key];

                                    if (empty($value)) {
                                        continue;
                                    }

                                    if (is_array($value)) {
                                        foreach ($value as $item) {
                                            if (is_numeric($item)) {
                                                $related_objects_meta[] = [
                                                    'id' => (int)$item,
                                                    'module' => $module_key,
                                                    'type' => $module_type
                                                ];
                                            } else {
                                                $related_objects_meta[] = [
                                                    'type' => $module_type,
                                                    'module' => $module_key,
                                                    'slug' => $item,
                                                ];
                                            }
                                        }
                                    } else if (is_string($value) && strpos($value, ',') !== false) {
                                        $items = array_map('trim', explode(',', $value));
                                        foreach ($items as $item) {
                                            if (is_numeric($item)) {
                                                $related_objects_meta[] = [
                                                    'id' => (int)$item,
                                                    'module' => $module_key,
                                                    'type' => $module_type
                                                ];
                                            } else {
                                                $related_objects_meta[] = [
                                                    'type' => $module_type,
                                                    'module' => $module_key,
                                                    'slug' => $item,
                                                ];
                                            }
                                        }
                                    } else {
                                        if (is_numeric($value)) {
                                            $related_objects_meta[] = [
                                                'id' => (int)$value,
                                                'module' => $module_key,
                                                'type' => $module_type
                                            ];
                                        } else {
                                            $related_objects_meta[] = [
                                                'type' => $module_type,
                                                'module' => $module_key,
                                                'slug' => $value,
                                            ];
                                        }
                                    }
                                }
                            }
                        }

                        $i++;
                    }

                    if ($linked_modules != [] && !empty($related_objects_meta)) {
                        $related_objects_core = $this->core->getObjects($related_objects_meta);

                        $rojt = [];
                        $id_rojt = [];

                        foreach ($related_objects_core as $related_object) {
                            if (isset($related_object['slug'])) {
                                $rojt[$related_object['type']][$related_object['slug']] = $related_object['id'];
                            }
                            $id_rojt[$related_object['id']] = $related_object;
                        }

                        $i = 0;
                        foreach ($objects as $object) {
                            $documents[$i] = $this->processLinkedModules(
                                $documents[$i],
                                $object,
                                $linked_modules,
                                $related_objects_core,
                                $rojt,
                                $id_rojt
                            );
                            $i++;
                        }
                    }

                    $document = CollectionDocument::fromResources(...$documents);

                    $totalObjectsCount= $this->core->getIDsTotalCount(
                        $search_array = array_merge(
                            ($_GET['filter'] ?? []),
                            ($_GET['modules'] ?? []),
                            array('type'=>$this->type)
                        ),
                        $limit,
                        $sort_field,
                        $sort_order,
                        $show_public_objects_only = (($_GET['show_public_objects_only'] === 'false' || $_GET['show_public_objects_only'] === false) ? boolval(false) : boolval(true)),
                        $ignore_ids = ($_GET['ignore_ids'] ?? []),
                        $show_partial_search_results = (($_GET['filter'] ?? false) ? boolval(true) : boolval(false)),
                        false, 'LIKE', 'OR', 'AND', ($_GET['range'] ?? [])
                    );

                    if ($_GET['sql']) {
                        $ids = $this->sql->executeSQL(explode('LIMIT', $_GET['sql'])[0]);
                        $document->addMeta('total_objects', count($ids));
                    }
                    else
                        $document->addMeta('total_objects', $totalObjectsCount);

                    $document->sendResponse();
                }

                else {
                    $documents = array();
                    $document = CollectionDocument::fromResources(...$documents);
                    $document->sendResponse();
                }
            }

            else if (($this->type ?? false) && ($this->id ?? false)) {

                if ($object = $this->core->getObject($this->id)) {
                    $document = new ResourceDocument($this->type, $object['id']);
                    $document->add('modules', $object);
                    $document->add('slug', $object['slug']);

                    if ($linked_modules != []) {
                        $document = $this->processLinkedModules($document, $object, $linked_modules);
                    }

                    if ($this->thisRequestHasApiAccess) {
                        $document->sendResponse();
                    } else if ($object['content_privacy'] == 'public') {
                        $document->sendResponse();
                    } else {
                        $error = [
                            'errors' => [[
                                'status' => '403',
                                'title' => 'Forbidden',
                                'detail' => 'You do not have permission to access this resource. If your request is using an API key in production mode, make sure it is from a whitelisted domain. Use Junction to generate API keys and whitelist your domains.'
                            ]]
                        ];
                        $this->json($error)->send(403);
                        die();
                    }
                } else {
                    $this->send(404);
                    die();
                }
            }

            else {
                $this->send(404);
                die();
            }
        }
    }

    /**
     * Handle search endpoint /api/v1.1/search
     */
    private function handleSearchEndpoint()
    {
        if (!$this->method('GET')) {
            $error = [
                'errors' => [[
                    'status' => '405',
                    'title' => 'Method Not Allowed',
                    'detail' => 'Search endpoint only supports GET method'
                ]]
            ];
            $this->json($error)->send(405);
        }

        $query = $_GET['q'] ?? '';
        $type = $_GET['type'] ?? null;
        $page = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
        $per_page = filter_var($_GET['per_page'] ?? 25, FILTER_VALIDATE_INT, ['options' => ['default' => 25, 'min_range' => 1, 'max_range' => 100]]);
        $sort_by = $_GET['sort_by'] ?? null;
        $facet_by = $_GET['facet_by'] ?? null;

        if (empty(trim($query))) {
            $error = [
                'errors' => [[
                    'status' => '400',
                    'title' => 'Bad Request',
                    'detail' => 'Search query parameter "q" is required and cannot be empty'
                ]]
            ];
            $this->json($error)->send(400);
        }

        if ($type) {
            $types = $this->config->getTypes();
            if (!isset($types[$type])) {
                $error = [
                    'errors' => [[
                        'status' => '400',
                        'title' => 'Bad Request',
                        'detail' => "Invalid type '{$type}'. Available types: " . implode(', ', array_keys($types))
                    ]]
                ];
                $this->json($error)->send(400);
            }
        }

        $searchOptions = [
            'type' => $type,
            'page' => $page,
            'per_page' => $per_page,
            'show_public_only' => !$this->thisRequestHasApiAccess,
        ];

        if ($sort_by) {
            $searchOptions['sort_by'] = $sort_by;
        }

        if ($facet_by) {
            $searchOptions['facet_by'] = $facet_by;
        }

        $filters = [];
        foreach ($_GET as $key => $value) {
            if (!in_array($key, ['q', 'type', 'page', 'per_page', 'sort_by', 'facet_by']) && !empty($value)) {
                $filters[$key] = $value;
            }
        }
        
        if (!empty($filters)) {
            $searchOptions['filters'] = $filters;
        }

        $searchResults = $this->core->searchObjects($query, $searchOptions);

        $documents = [];
        $i = 0;
        
        if (!empty($searchResults['objects'])) {
            foreach ($searchResults['objects'] as $object) {
                $document = new ResourceDocument($object['type'], $object['id']);
                $document->add('modules', $object);
                $document->add('slug', $object['slug'] ?? '');
                
                if (isset($searchResults['highlights']) && isset($searchResults['highlights'][$object['id']])) {
                    $document->add('search_highlights', $searchResults['highlights'][$object['id']]);
                }
                
                $documents[] = $document;
            }
        }

        $document = CollectionDocument::fromResources(...$documents);

        $searchMeta = [
            'total_found' => $searchResults['total_found'] ?? 0,
            'search_time_ms' => $searchResults['search_time_ms'] ?? 0,
            'search_source' => $searchResults['source'] ?? 'unknown',
            'query' => $query,
            'page' => $page,
            'per_page' => $per_page,
        ];

        if (!empty($searchResults['facet_counts'])) {
            $searchMeta['facet_counts'] = $searchResults['facet_counts'];
        }

        $document->addMeta('search', $searchMeta);
        $document->sendResponse();
    }

    /**
     * Save the types/blueprint to the active blueprint_record.
     * The webapp type itself is never stored as its own DB row —
     * it lives inside the blueprint_record's `blueprint` JSON field.
     */
    public function pushTypesObject($object)
    {
        $modules = $object['data']['attributes']['modules'];
        $json    = json_encode($modules, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        // ── Primary: save to the active blueprint_record ─────────────────
        $dbSaved = false;
        try {
            $activeBlueprintId = $this->findActiveBlueprintRecordId();

            if ($activeBlueprintId) {
                // Update the existing active blueprint_record
                $existingRecord = $this->core->getObject($activeBlueprintId);
                $existingRecord['blueprint'] = $json;
                $this->core->pushObject($existingRecord);
                $dbSaved = true;
            } else {
                // No active blueprint_record exists — create one
                $title = $modules['webapp']['name'] ?? 'Blueprint';
                $newRecord = [
                    'type'      => 'blueprint_record',
                    'title'     => $title,
                    'active'    => true,
                    'blueprint' => $json,
                    'content_privacy' => 'private',
                ];
                $this->core->pushObject($newRecord);
                $dbSaved = true;
            }
        } catch (\Throwable $e) {
            error_log('[pushTypesObject] blueprint_record save failed: ' . $e->getMessage());
        }

        // ── Fallback: write to folder if DB save failed ───────────────────
        if (!$dbSaved) {
            $folder_path = 'uploads/types';
            if (!is_dir($folder_path)) {
                mkdir($folder_path, 0755, true);
            }
            $types_file_path = $folder_path . '/types-' . time() . '.json';
            file_put_contents($types_file_path, $json);
        }

        unset($object['attributes']);
    }

    /**
     * Find the DB id of the currently active blueprint_record.
     * Returns the integer id, or null if none is active.
     */
    private function findActiveBlueprintRecordId(): ?int
    {
        // Try string "true"
        $rows = $this->sql->executeSQL(
            "SELECT `id` FROM `data`
             WHERE `content`->>'$.type' = 'blueprint_record'
               AND `content`->>'$.active' = 'true'
             ORDER BY `id` DESC
             LIMIT 1"
        );

        if (!empty($rows[0]['id'])) {
            return (int) $rows[0]['id'];
        }

        // Try JSON boolean true
        $rows = $this->sql->executeSQL(
            "SELECT `id` FROM `data`
             WHERE `content`->>'$.type' = 'blueprint_record'
               AND JSON_EXTRACT(`content`, '$.active') = true
             ORDER BY `id` DESC
             LIMIT 1"
        );

        if (!empty($rows[0]['id'])) {
            return (int) $rows[0]['id'];
        }

        return null;
    }

    private function parseSizeToGB(string $sizeRaw): string {
        $parts = preg_split('/\s+/', $sizeRaw, -1, PREG_SPLIT_NO_EMPTY)[0];
        $numeric = isset($parts[0]) ? trim($parts[0]) : '';

        if (!is_numeric($numeric)) {
            error_log("Warning: Non-numeric size encountered: '{$sizeRaw}'");
            return '0.00';
        }

        return number_format((float)$numeric / 1024 / 1024, 2, '.', '');
    }

    public function getTypesObject() {
        global $_ENV;

        // ── 1. Load the blueprint from the active blueprint_record ───────
        $blueprint = null;
        try {
            $activeBlueprintId = $this->findActiveBlueprintRecordId();

            if ($activeBlueprintId) {
                $bpRecord = $this->core->getObject($activeBlueprintId);
                if (!empty($bpRecord['blueprint'])) {
                    $decoded = json_decode($bpRecord['blueprint'], true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $blueprint = $decoded;
                    }
                }
            }
        } catch (\Throwable $e) {
            error_log('[getTypesObject] blueprint_record load failed: ' . $e->getMessage());
        }

        // ── 2. Fall back to folder-based blueprint if DB had nothing ─────
        if ($blueprint === null) {
            $folder_path = 'uploads/types';
            if (is_dir($folder_path)) {
                $files = glob($folder_path . '/types-*.json');
                if (!empty($files)) {
                    usort($files, fn($a, $b) => filemtime($b) - filemtime($a));
                    $raw = file_get_contents($files[0]);
                    $decoded = json_decode($raw, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $blueprint = $decoded;
                    }
                }
            }
        }

        // ── 3. Base object comes from config; merge blueprint on top ─────
        $object = $this->config->getTypes();
        if ($blueprint !== null) {
            $object = array_replace_recursive($object, $blueprint);
        }

        // ── 4. Conditionally calculate per-type total_objects ─────────────
        $cacheWebappTotalObjects = filter_var(
            $_ENV['CACHE_WEBAPP_TOTAL_OBJECTS'] ?? false,
            FILTER_VALIDATE_BOOLEAN
        );

        $includeParam = $_GET['include'] ?? '';
        $includeTotalObjects = (strpos($includeParam, 'total_objects') !== false);

        if (!$cacheWebappTotalObjects && $includeTotalObjects) {
            // Live count — suitable for small-to-medium databases
            $typeCounts = $this->core->getTypeObjectsCounts(array_keys($object));

            foreach ($object as $key => $value) {
                $object[$key]['total_objects'] = $typeCounts[$key] ?? 0;
            }

            $sizeRaw = $this->core->executeShellCommand('du -s uploads');
            $objectsCount = $this->sql->executeSQL("SELECT COUNT(*) AS `count` FROM `data`");
            $object['webapp']['size_in_gb']    = $this->parseSizeToGB($sizeRaw);
            $object['webapp']['total_objects'] = $objectsCount[0]['count'];
        } else if ($cacheWebappTotalObjects && $includeTotalObjects) {
            // Use cached counts from the blueprint_record itself (for large DBs).
            // Counts are populated by recalculateTotalObjects().
            // If no cached counts exist yet, serve zeros rather than blocking.
        }
        // If total_objects is not requested, skip both live and cached counting.

        $document = new ResourceDocument($this->type, 0);
        $document->add('modules', $object);
        $document->add('slug', ($object['slug'] ?? 'webapp'));
        $document->sendResponse();
    }

    /**
     * Recalculate and persist total_objects for all types.
     * Call this when CACHE_WEBAPP_TOTAL_OBJECTS=true and a fresh
     * count is explicitly needed (e.g. from a scheduled job or admin action).
     *
     * Stores counts inside the active blueprint_record's blueprint JSON.
     */
    public function recalculateTotalObjects(): void
    {
        $object = $this->config->getTypes();

        $typeCounts   = $this->core->getTypeObjectsCounts(array_keys($object));
        $objectsCount = $this->sql->executeSQL("SELECT COUNT(*) AS `count` FROM `data`");
        $sizeRaw      = $this->core->executeShellCommand('du -s uploads');

        foreach ($object as $key => $value) {
            $object[$key]['total_objects'] = $typeCounts[$key] ?? 0;
        }
        $object['webapp']['size_in_gb']    = $this->parseSizeToGB($sizeRaw);
        $object['webapp']['total_objects'] = $objectsCount[0]['count'];

        // Persist the updated counts into the active blueprint_record
        try {
            $activeBlueprintId = $this->findActiveBlueprintRecordId();

            if ($activeBlueprintId) {
                $bpRecord = $this->core->getObject($activeBlueprintId);
                $existingBlueprint = [];
                if (!empty($bpRecord['blueprint'])) {
                    $existingBlueprint = json_decode($bpRecord['blueprint'], true) ?? [];
                }
                foreach ($object as $key => $value) {
                    $existingBlueprint[$key]['total_objects'] = $value['total_objects'];
                }
                $existingBlueprint['webapp']['size_in_gb'] = $object['webapp']['size_in_gb'];

                $bpRecord['blueprint'] = json_encode(
                    $existingBlueprint,
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                );
                $this->core->pushObject($bpRecord);
            }
        } catch (\Throwable $e) {
            error_log('[recalculateTotalObjects] blueprint_record update failed: ' . $e->getMessage());
        }
    }

    /**
     * returns the request body as an array
     */
    public function body(): array
    {
        return $this->requestBody;
    }

    /**
     * encodes passed data as a json that can be sent over network
     */
    public function json($data): Api
    {
        $encodeOptions =  JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PARTIAL_OUTPUT_ON_ERROR;
        $this->response = json_encode($data, $encodeOptions);
        return $this;
    }

    /**
     * sets http code to response and responds to the request
     */
    #[NoReturn]
    public function send(int $status_code = 200)
    {
        header('Content-Type: application/vnd.api+json');

        $origin = $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_HOST'] ?? '*';
        header("Access-Control-Allow-Origin: $origin");
        header("Access-Control-Allow-Credentials: true");

        http_response_code($status_code);

        echo $this->response;
        die();
    }

    /**
     * validates request method for API calls
     */
    public function method(?string $reqMethod = null)
    {
        if (!$reqMethod) {
            return strtolower($_SERVER['REQUEST_METHOD']);
        }

        $serverMethod = strtolower($_SERVER['REQUEST_METHOD']);
        $reqMethod = strtolower($reqMethod);

        return $serverMethod === $reqMethod;
    }

    public function isValidJsonRequest()
    {
        $error = 0;
        $requestHeaders = $this->getRequestHeaders();

        if (is_array($requestHeaders['Content-Type']) && in_array('application/vnd.api+json', $requestHeaders['Content-Type'])) {
            $error = 1;
        } else if (strstr($requestHeaders['Content-Type'], 'application/vnd.api+json')) {
            $error = 1;
        }
        if ($error) {
            $this->send(415);
            die();
        } else {
            return true;
        }
    }

    public function guidv4($data = null)
    {
        $data = $data ?? random_bytes(16);
        assert(strlen($data) == 16);

        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    public function exposeTribeApi(array $url_parts, array $all_types): void
    {
        require __DIR__."/../API/v1/handler.php";
        return;
    }

    /**
     * If the object is a file_record and transcription is enabled,
     * extract text via Tika or PaddleOCR
     */
    private function transcribeIfFileRecord(array $object): array
    {
        if (($object['type'] ?? '') !== 'file_record') {
            return $object;
        }

        if (empty($object['url'])) {
            return $object;
        }

        try {
            $transcriber = new \Tribe\Transcriber();

            if (!$transcriber->isEnabled()) {
                return $object;
            }

            $result = $transcriber->transcribe($object['url']);

            if ($result && !empty($result['transcription']['text'])) {
                $object['transcription'] = $result['transcription'];
                $this->core->pushObject($object);
                $object = $this->core->getObject($object['id']);
            }
        } catch (\Throwable $e) {
            error_log('[Transcriber] Failed to transcribe file_record ' . ($object['id'] ?? '?') . ': ' . $e->getMessage());
        }

        return $object;
    }
}
