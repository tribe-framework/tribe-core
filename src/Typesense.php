<?php
namespace Tribe;

use \Typesense\Client;
use \Typesense\Exceptions\TypesenseClientError;
use \Http\Client\Exception\NetworkException;

/**
 * Typesense integration for Tribe.
 *
 * KEY FIXES vs. original:
 *  1. Client constructor: added `protocol`, `connection_timeout_seconds`, and
 *     a `healthcheck_interval_seconds` so the SDK doesn't spin on every call.
 *  2. buildCollectionSchema: `id` must be `string` in Typesense (the SDK
 *     serialises document IDs as strings – using int64 causes upsert failures).
 *  3. buildCollectionSchema: `content_privacy` field added so privacy filters
 *     work correctly.
 *  4. transformObjectToDocument: `id` is cast to `(string)`, never `(int)`.
 *  5. updateDocument: uses `upsert` instead of `update` so documents that were
 *     never indexed (e.g. created while Typesense was down) get created rather
 *     than silently dropped.
 *  6. search: fixed multi-collection result normalisation – multi-search returns
 *     `results[n][hits]`, not a flat `hits` array.
 *  7. search: privacy filter is now applied correctly to multi-search too.
 *  8. isHealthy: catches generic \Exception in addition to TypesenseClientError
 *     so a network-level failure doesn't bubble up and kill Core::__construct.
 *  9. updateCollectionIfNeeded: the update payload must be
 *     `['fields' => [...]]`, not a bare array.
 * 10. getCollectionName: made public so external tools (search.php) can resolve
 *     the same name without duplicating logic.
 */
class Typesense {
    private $client;
    private $config;

    public function __construct()
    {
        $this->config = new Config();

        $host = $_ENV['TYPESENSE_HOST']
            ?? (($_ENV['PROJECT_NAME'] ?? 'tribe') . '_typesense');

        $this->client = new Client([
            'api_key' => $_ENV['TYPESENSE_API_KEY'] ?? 'xyz',
            'nodes'   => [[
                'host'     => $host,
                'port'     => (string)($_ENV['TYPESENSE_PORT'] ?? '8108'),
                'protocol' => 'http',
            ]],
            'connection_timeout_seconds'    => 5,
            'healthcheck_interval_seconds'  => 60,
        ]);
    }

    // ─── Public helpers ───────────────────────────────────────────────────────

    /**
     * Expose the raw Typesense SDK client.
     * Used by index_db.php and reindex_db.php so they can call the SDK
     * directly without duplicating connection logic.
     */
    public function getClient(): \Typesense\Client
    {
        return $this->client;
    }

    /**
     * Expose collection name so external code (search.php, status.php, …)
     * can reference the same naming convention.
     */
    public function getCollectionName(string $type): string
    {
        return "tribe_{$type}";
    }

    // ─── Collection management ────────────────────────────────────────────────

    /**
     * Create or update a collection schema for a specific type.
     */
    public function createOrUpdateCollection(string $type)
    {
        $types = $this->config->getTypes();

        if (!isset($types[$type])) {
            error_log("Typesense: type '{$type}' not found in configuration");
            return false;
        }

        $collectionName = $this->getCollectionName($type);
        $schema         = $this->buildCollectionSchema($type, $types[$type]);

        try {
            $existing = $this->client->collections[$collectionName]->retrieve();
            return $this->updateCollectionIfNeeded($collectionName, $schema, $existing);
        } catch (\Exception $e) {
            // Collection does not exist – create it
            try {
                return $this->client->collections->create($schema);
            } catch (\Exception $e2) {
                error_log("Typesense: failed to create collection '{$collectionName}': " . $e2->getMessage());
                return false;
            }
        }
    }

    /**
     * Build Typesense collection schema from type configuration.
     *
     * FIX: `id` must be 'string' (Typesense requirement).
     * FIX: `content_privacy` field added for public/private filtering.
     */
    private function buildCollectionSchema(string $type, array $typeConfig): array
    {
        $fields = [
            // id MUST be string in Typesense
            ['name' => 'id',              'type' => 'string', 'facet' => false],
            ['name' => 'type',            'type' => 'string', 'facet' => true],
            ['name' => 'slug',            'type' => 'string', 'facet' => false, 'optional' => true],
            ['name' => 'content_privacy', 'type' => 'string', 'facet' => true,  'optional' => true],
            ['name' => 'created_on',      'type' => 'int64',  'facet' => false, 'optional' => true],
            ['name' => 'updated_on',      'type' => 'int64',  'facet' => false, 'optional' => true],
            ['name' => 'user_id',         'type' => 'int64',  'facet' => true,  'optional' => true],
            // Aggregated searchable text
            ['name' => 'search_content',  'type' => 'string', 'facet' => false, 'optional' => true],
        ];

        if (isset($typeConfig['modules'])) {
            foreach ($typeConfig['modules'] as $module) {
                $fieldName = $module['input_slug'] ?? null;
                if (!$fieldName) continue;

                // Skip fields already defined above
                if (in_array($fieldName, ['id','type','slug','content_privacy',
                                           'created_on','updated_on','user_id','search_content'])) {
                    continue;
                }

                $fieldType = $this->mapInputTypeToTypesense($module['input_type'] ?? 'text');

                $field = [
                    'name'     => $fieldName,
                    'type'     => $fieldType,
                    'facet'    => $this->shouldFieldBeFacet($module),
                    'optional' => true,
                    'index'    => true,
                ];

                $fields[] = $field;
            }
        }

        return [
            'name'                   => $this->getCollectionName($type),
            'fields'                 => $fields,
            'default_sorting_field'  => 'updated_on',
            'token_separators'       => ['-', '_', '.', '@'],
            'symbols_to_index'       => ['@', '#'],
        ];
    }

    private function mapInputTypeToTypesense(string $inputType): string
    {
        return [
            'text'     => 'string',
            'textarea' => 'string',
            'select'   => 'string',
            'radio'    => 'string',
            'checkbox' => 'bool',
            'number'   => 'int64',
            'float'    => 'float',
            'date'     => 'int64',
            'email'    => 'string',
            'url'      => 'string',
            'file'     => 'string',
            'image'    => 'string',
            'password' => 'string',
        ][$inputType] ?? 'string';
    }

    private function shouldFieldBeFacet(array $module): bool
    {
        return in_array($module['input_type'] ?? '', ['select', 'radio', 'checkbox'])
            || ($module['list_field'] ?? false) === true;
    }

    // ─── Document CRUD ────────────────────────────────────────────────────────

    /**
     * Index a new document.
     * Uses `upsert` so a retry after a transient failure never leaves a
     * duplicate.
     */
    public function indexDocument(array $object)
    {
        if (!isset($object['type'], $object['id'])) {
            error_log('Typesense::indexDocument – missing type or id');
            return false;
        }

        $collectionName = $this->getCollectionName($object['type']);
        $this->createOrUpdateCollection($object['type']);

        $document = $this->transformObjectToDocument($object);

        try {
            return $this->client->collections[$collectionName]
                ->documents->upsert($document);
        } catch (\Exception $e) {
            error_log("Typesense::indexDocument failed [{$object['id']}]: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update an existing document.
     *
     * FIX: uses `upsert` instead of `update` so documents missing from the
     * index (e.g. after a reset) are re-created transparently.
     */
    public function updateDocument(array $object)
    {
        if (!isset($object['type'], $object['id'])) {
            return false;
        }

        $collectionName = $this->getCollectionName($object['type']);
        $this->createOrUpdateCollection($object['type']);

        $document = $this->transformObjectToDocument($object);

        try {
            return $this->client->collections[$collectionName]
                ->documents->upsert($document);
        } catch (\Exception $e) {
            error_log("Typesense::updateDocument failed [{$object['id']}]: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a document from its type collection.
     */
    public function deleteDocument($id, string $type)
    {
        $collectionName = $this->getCollectionName($type);

        try {
            return $this->client->collections[$collectionName]
                ->documents[(string)$id]->delete();
        } catch (\Exception $e) {
            error_log("Typesense::deleteDocument failed [{$id}]: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Transform a Tribe object array into a Typesense document.
     *
     * FIX: `id` is always cast to `(string)`.
     */
    private function transformObjectToDocument(array $object): array
    {
        $document = [
            'id'              => (string)$object['id'],   // MUST be string
            'type'            => $object['type'],
            'slug'            => $object['slug'] ?? '',
            'content_privacy' => $object['content_privacy'] ?? 'public',
            'created_on'      => (int)($object['created_on'] ?? time()),
            'updated_on'      => (int)($object['updated_on'] ?? time()),
        ];

        if (isset($object['user_id'])) {
            $document['user_id'] = (int)$object['user_id'];
        }

        $searchContent = [];
        $types = $this->config->getTypes();

        if (isset($types[$object['type']]['modules'])) {
            foreach ($types[$object['type']]['modules'] as $module) {
                $fieldName = $module['input_slug'] ?? null;
                if (!$fieldName) continue;

                if (!array_key_exists($fieldName, $object) || $object[$fieldName] === null || $object[$fieldName] === '') {
                    continue;
                }

                $value = $object[$fieldName];

                $document[$fieldName] = $this->castFieldValue($value, $module['input_type'] ?? 'text');

                if ($this->isFieldSearchable($module)) {
                    if (is_array($value)) {
                        $searchContent[] = implode(' ', array_filter($value, 'is_string'));
                    } elseif (is_string($value)) {
                        $searchContent[] = $value;
                    }
                }
            }
        }

        $document['search_content'] = implode(' ', array_filter($searchContent));

        return $document;
    }

    private function castFieldValue($value, string $inputType)
    {
        switch ($inputType) {
            case 'number': return (int)$value;
            case 'float':  return (float)$value;
            case 'checkbox': return (bool)$value;
            case 'date':   return is_numeric($value) ? (int)$value : (int)strtotime((string)$value);
            default:
                return is_array($value) ? implode(', ', $value) : (string)$value;
        }
    }

    private function isFieldSearchable(array $module): bool
    {
        return in_array($module['input_type'] ?? '', ['text', 'textarea', 'select', 'radio']);
    }

    // ─── Search ───────────────────────────────────────────────────────────────

    /**
     * Perform a search across one or all type collections.
     *
     * FIX: multi-search response structure is `results[n]` each with `hits`,
     * not a single flat `hits`.  Results are now merged and normalised
     * uniformly.
     * FIX: privacy filter applied correctly to multi-search.
     */
    public function search(string $query, array $options = [])
    {
        $perPage    = (int)($options['per_page'] ?? 25);
        $page       = (int)($options['page'] ?? 1);
        $numTypos   = (int)($options['num_typos'] ?? 2);
        $queryBy    = $options['query_by'] ?? 'search_content';
        $targetType = $options['type'] ?? null;

        // Resolve show_public_only:
        //  1. Explicit per-call override in $options takes highest precedence.
        //  2. Falls back to TYPESENSE_SHOW_PUBLIC_OBJECTS_ONLY env flag.
        //  3. Defaults to true when neither is set.
        if (array_key_exists('show_public_only', $options)) {
            $showPublicOnly = (bool)$options['show_public_only'];
        } else {
            $envVal = $_ENV['TYPESENSE_SHOW_PUBLIC_OBJECTS_ONLY'] ?? 'true';
            $showPublicOnly = (strtolower(trim($envVal)) !== 'false');
        }

        // Build privacy + extra filter string
        $filters = [];

        if ($showPublicOnly) {
            $filters[] = 'content_privacy:=public';
        }

        if ($targetType) {
            $filters[] = "type:={$targetType}";
        }

        if (!empty($options['filters']) && is_array($options['filters'])) {
            foreach ($options['filters'] as $field => $value) {
                $filters[] = is_array($value)
                    ? "{$field}:[" . implode(',', $value) . "]"
                    : "{$field}:={$value}";
            }
        }

        $filterBy = implode(' && ', $filters);

        $baseParams = [
            'q'                         => $query,
            'query_by'                  => $queryBy,
            'per_page'                  => $perPage,
            'page'                      => $page,
            'num_typos'                 => $numTypos,
            'highlight_full_fields'     => $queryBy,
            'highlight_affix_num_tokens'=> 3,
        ];

        if ($filterBy) {
            $baseParams['filter_by'] = $filterBy;
        }

        if (!empty($options['facet_by'])) {
            $baseParams['facet_by'] = is_array($options['facet_by'])
                ? implode(',', $options['facet_by'])
                : $options['facet_by'];
        }

        if (!empty($options['sort_by'])) {
            $baseParams['sort_by'] = $options['sort_by'];
        }

        try {
            if ($targetType) {
                // Single-collection search
                $collectionName = $this->getCollectionName($targetType);
                $raw = $this->client->collections[$collectionName]
                    ->documents->search($baseParams);

                return $this->normaliseSingleResult($raw);
            }

            // Multi-collection search
            $types   = array_keys($this->config->getTypes());
            $searches = [];

            foreach ($types as $type) {
                if ($type === 'webapp') continue;

                $req = array_merge($baseParams, [
                    'collection' => $this->getCollectionName($type),
                ]);

                $searches[] = $req;
            }

            if (empty($searches)) {
                return $this->emptyResult();
            }

            $raw = $this->client->multiSearch->perform(
                ['searches' => $searches],
                ['query_by' => $queryBy]
            );

            return $this->normaliseMultiResult($raw);

        } catch (\Exception $e) {
            error_log("Typesense::search failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Normalise a single-collection search response to the common format
     * expected by Core::searchObjects.
     */
    private function normaliseSingleResult(array $raw): array
    {
        return [
            'hits'          => $raw['hits'] ?? [],
            'found'         => $raw['found'] ?? 0,
            'search_time_ms'=> $raw['search_time_ms'] ?? 0,
            'facet_counts'  => $raw['facet_counts'] ?? [],
        ];
    }

    /**
     * Normalise a multi-search response.
     *
     * FIX: The SDK returns `['results' => [['hits'=>…,'found'=>…], …]]`.
     * We flatten all hits into one array and sum found counts.
     */
    private function normaliseMultiResult(array $raw): array
    {
        $allHits     = [];
        $totalFound  = 0;
        $searchTimeMs= 0;
        $facetCounts = [];

        foreach ($raw['results'] ?? [] as $result) {
            $allHits      = array_merge($allHits, $result['hits'] ?? []);
            $totalFound  += (int)($result['found'] ?? 0);
            $searchTimeMs = max($searchTimeMs, (int)($result['search_time_ms'] ?? 0));

            if (!empty($result['facet_counts'])) {
                $facetCounts = array_merge($facetCounts, $result['facet_counts']);
            }
        }

        return [
            'hits'          => $allHits,
            'found'         => $totalFound,
            'search_time_ms'=> $searchTimeMs,
            'facet_counts'  => $facetCounts,
        ];
    }

    private function emptyResult(): array
    {
        return ['hits' => [], 'found' => 0, 'search_time_ms' => 0, 'facet_counts' => []];
    }

    // ─── Suggestions ──────────────────────────────────────────────────────────

    public function getSuggestions(string $query, ?string $type = null, int $limit = 10)
    {
        return $this->search($query, [
            'per_page' => $limit,
            'type'     => $type,
            'prefix'   => true,
        ]);
    }

    // ─── Bulk operations ──────────────────────────────────────────────────────

    public function bulkIndex(array $objects, int $batchSize = 100): array
    {
        $results = [];

        foreach (array_chunk($objects, $batchSize) as $batch) {
            $byType = [];

            foreach ($batch as $object) {
                if (!isset($object['type'])) continue;
                $byType[$object['type']][] = $this->transformObjectToDocument($object);
            }

            foreach ($byType as $type => $docs) {
                // Ensure collection exists before bulk import
                $this->createOrUpdateCollection($type);
                $collectionName = $this->getCollectionName($type);

                try {
                    $results[$type] = $this->client->collections[$collectionName]
                        ->documents->import($docs, ['action' => 'upsert']);
                } catch (\Exception $e) {
                    error_log("Typesense::bulkIndex failed for '{$type}': " . $e->getMessage());
                    $results[$type] = ['error' => $e->getMessage()];
                }
            }
        }

        return $results;
    }

    // ─── Health ───────────────────────────────────────────────────────────────

    /**
     * FIX: catches generic \Exception so a network error doesn't propagate
     * and crash Core::__construct.
     */
    public function isHealthy(): bool
    {
        try {
            $response = $this->client->health->retrieve(3);
            return isset($response['ok']) && $response['ok'] === true;
        } catch (\Exception $e) {
            error_log("Typesense::isHealthy failed: " . $e->getMessage());
            return false;
        }
    }

    // ─── Schema updates ───────────────────────────────────────────────────────

    /**
     * FIX: the update payload must be `['fields' => [...]]`, not a bare array.
     */
    private function updateCollectionIfNeeded(string $collectionName, array $newSchema, array $existing): bool
    {
        try {
            $existingFieldNames = array_column($existing['fields'] ?? [], 'name');
            $missingFields      = [];

            foreach ($newSchema['fields'] as $field) {
                if (!in_array($field['name'], $existingFieldNames)) {
                    $missingFields[] = $field;
                }
            }

            if (!empty($missingFields)) {
                $this->client->collections[$collectionName]->update([
                    'fields' => $missingFields,
                ]);
            }

            return true;
        } catch (\Exception $e) {
            error_log("Typesense::updateCollectionIfNeeded failed for '{$collectionName}': " . $e->getMessage());
            return false;
        }
    }
}