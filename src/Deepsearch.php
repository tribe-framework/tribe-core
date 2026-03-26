<?php
namespace Tribe;

/**
 * Deepsearch — Meilisearch integration for Tribe.
 *
 * Standalone replacement for Typesense.php. Uses Meilisearch's REST API
 * directly via cURL (no SDK dependency). All variable names use "deepsearch"
 * internally; the actual engine is Meilisearch.
 *
 * Environment variables:
 *   DEEPSEARCH_HOST      container hostname      (default: {PROJECT_NAME}_deepsearch)
 *   DEEPSEARCH_PORT      HTTP port               (default: 8108)
 *   DEEPSEARCH_API_KEY   master/admin API key    (default: xyz)
 */
class Deepsearch {
    private string $host;
    private int    $port;
    private string $apiKey;
    private $config;

    public function __construct()
    {
        $this->config = new Config();

        $this->host   = $_ENV['DEEPSEARCH_HOST']
            ?? (($_ENV['PROJECT_NAME'] ?? 'tribe') . '_deepsearch');
        $this->port   = (int)($_ENV['DEEPSEARCH_PORT'] ?? 8108);
        $this->apiKey = $_ENV['DEEPSEARCH_API_KEY'] ?? 'xyz';
    }

    // ─── HTTP primitives ──────────────────────────────────────────────────────

    private function request(string $method, string $path, ?array $body = null, int $timeout = 10): array
    {
        $url = "http://{$this->host}:{$this->port}{$path}";
        $ch  = curl_init($url);

        $headers = [
            "Authorization: Bearer {$this->apiKey}",
            'Content-Type: application/json',
        ];

        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_HTTPHEADER     => $headers,
        ]);

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }

        $raw  = curl_exec($ch);
        $err  = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw === false || $err) {
            return ['_error' => "curl failed: {$err}", '_code' => 0];
        }

        $data = json_decode($raw, true) ?? [];
        $data['_code'] = $code;
        return $data;
    }

    private function get(string $path, int $timeout = 10): array
    {
        return $this->request('GET', $path, null, $timeout);
    }

    private function post(string $path, array $body, int $timeout = 10): array
    {
        return $this->request('POST', $path, $body, $timeout);
    }

    private function put(string $path, array $body, int $timeout = 10): array
    {
        return $this->request('PUT', $path, $body, $timeout);
    }

    private function delete(string $path, int $timeout = 10): array
    {
        return $this->request('DELETE', $path, null, $timeout);
    }

    // ─── Public helpers ───────────────────────────────────────────────────────

    /**
     * Expose index (collection) name so external code can reference the
     * same naming convention without duplication.
     */
    public function getCollectionName(string $type): string
    {
        return "tribe_{$type}";
    }

    // ─── Index management ─────────────────────────────────────────────────────

    /**
     * Ensure a Meilisearch index exists for the given Tribe type.
     * Creates the index if missing, then configures searchable/filterable attributes.
     */
    public function createOrUpdateCollection(string $type): bool
    {
        $indexName = $this->getCollectionName($type);

        // Try to get index info — 200 means it exists
        $info = $this->get("/indexes/{$indexName}");
        if (($info['_code'] ?? 0) === 404 || isset($info['_error'])) {
            // Create index with 'id' as primary key
            $create = $this->post('/indexes', [
                'uid'        => $indexName,
                'primaryKey' => 'id',
            ]);
            if (($create['_code'] ?? 0) >= 400 && ($create['_code'] ?? 0) !== 202) {
                error_log("Deepsearch: failed to create index '{$indexName}': " . json_encode($create));
                return false;
            }
        }

        // Configure filterable and sortable attributes
        $this->put("/indexes/{$indexName}/settings", [
            'searchableAttributes' => ['search_content', 'slug', 'type'],
            'filterableAttributes' => ['type', 'content_privacy', 'user_id', 'updated_on', 'created_on'],
            'sortableAttributes'   => ['updated_on', 'created_on'],
            'typoTolerance'        => ['enabled' => true, 'minWordSizeForTypos' => ['oneTypo' => 4, 'twoTypos' => 8]],
        ]);

        return true;
    }

    // ─── Document CRUD ────────────────────────────────────────────────────────

    /**
     * Index a new document (upsert).
     */
    public function indexDocument(array $object): bool
    {
        if (!isset($object['type'], $object['id'])) {
            error_log('Deepsearch::indexDocument – missing type or id');
            return false;
        }

        $indexName = $this->getCollectionName($object['type']);
        $this->createOrUpdateCollection($object['type']);

        $document = $this->transformObjectToDocument($object);

        $result = $this->post("/indexes/{$indexName}/documents", [$document]);
        if (($result['_code'] ?? 0) >= 400 && ($result['_code'] ?? 0) !== 202) {
            error_log("Deepsearch::indexDocument failed [{$object['id']}]: " . json_encode($result));
            return false;
        }
        return true;
    }

    /**
     * Update (upsert) a document.
     */
    public function updateDocument(array $object): bool
    {
        if (!isset($object['type'], $object['id'])) {
            return false;
        }

        $indexName = $this->getCollectionName($object['type']);
        $this->createOrUpdateCollection($object['type']);

        $document = $this->transformObjectToDocument($object);

        $result = $this->put("/indexes/{$indexName}/documents", [$document]);
        if (($result['_code'] ?? 0) >= 400 && ($result['_code'] ?? 0) !== 202) {
            error_log("Deepsearch::updateDocument failed [{$object['id']}]: " . json_encode($result));
            return false;
        }
        return true;
    }

    /**
     * Delete a document from its type index.
     */
    public function deleteDocument($id, string $type): bool
    {
        $indexName = $this->getCollectionName($type);

        $result = $this->delete("/indexes/{$indexName}/documents/{$id}");
        if (($result['_code'] ?? 0) >= 400 && ($result['_code'] ?? 0) !== 202) {
            error_log("Deepsearch::deleteDocument failed [{$id}]: " . json_encode($result));
            return false;
        }
        return true;
    }

    /**
     * Transform a Tribe object into a Meilisearch document.
     */
    private function transformObjectToDocument(array $object): array
    {
        $document = [
            'id'              => (string)$object['id'],
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

                // Store field value as string for Meilisearch
                if (is_array($value)) {
                    $document[$fieldName] = implode(', ', array_filter($value, 'is_scalar'));
                } elseif (is_bool($value)) {
                    $document[$fieldName] = $value;
                } else {
                    $document[$fieldName] = $value;
                }

                // Aggregate searchable text
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

    private function isFieldSearchable(array $module): bool
    {
        return in_array($module['input_type'] ?? '', ['text', 'textarea', 'select', 'radio']);
    }

    // ─── Search ───────────────────────────────────────────────────────────────

    /**
     * Search across one or all type indexes.
     * Returns normalised format: { hits, found, search_time_ms, facet_counts }
     */
    public function search(string $query, array $options = [])
    {
        $perPage    = (int)($options['per_page'] ?? 25);
        $page       = (int)($options['page'] ?? 1);
        $targetType = $options['type'] ?? null;

        // Resolve public-only filter
        if (array_key_exists('show_public_only', $options)) {
            $showPublicOnly = (bool)$options['show_public_only'];
        } else {
            $envVal = $_ENV['DEEPSEARCH_SHOW_PUBLIC_OBJECTS_ONLY'] ?? 'true';
            $showPublicOnly = (strtolower(trim($envVal)) !== 'false');
        }

        // Build Meilisearch filter
        $filters = [];
        if ($showPublicOnly) {
            $filters[] = 'content_privacy = "public"';
        }
        if ($targetType) {
            $filters[] = "type = \"{$targetType}\"";
        }
        if (!empty($options['filters']) && is_array($options['filters'])) {
            foreach ($options['filters'] as $field => $value) {
                if (is_array($value)) {
                    $vals = implode('", "', $value);
                    $filters[] = "{$field} IN [\"{$vals}\"]";
                } else {
                    $filters[] = "{$field} = \"{$value}\"";
                }
            }
        }

        $searchBody = [
            'q'      => $query,
            'limit'  => $perPage,
            'offset' => ($page - 1) * $perPage,
        ];

        if (!empty($filters)) {
            $searchBody['filter'] = implode(' AND ', $filters);
        }

        if (!empty($options['sort_by'])) {
            $searchBody['sort'] = [$options['sort_by']];
        }

        try {
            if ($targetType) {
                // Single-index search
                $indexName = $this->getCollectionName($targetType);
                $raw = $this->post("/indexes/{$indexName}/search", $searchBody);

                if (($raw['_code'] ?? 0) >= 400) {
                    error_log("Deepsearch::search failed for index '{$indexName}': " . json_encode($raw));
                    return false;
                }
                return $this->normaliseResult($raw);
            }

            // Multi-index search
            $types    = array_keys($this->config->getTypes());
            $queries  = [];

            foreach ($types as $type) {
                if ($type === 'webapp') continue;
                $q = $searchBody;
                $q['indexUid'] = $this->getCollectionName($type);
                $queries[] = $q;
            }

            if (empty($queries)) {
                return $this->emptyResult();
            }

            $raw = $this->post('/multi-search', ['queries' => $queries]);

            if (($raw['_code'] ?? 0) >= 400) {
                error_log("Deepsearch::search multi-search failed: " . json_encode($raw));
                return false;
            }

            return $this->normaliseMultiResult($raw);

        } catch (\Exception $e) {
            error_log("Deepsearch::search failed: " . $e->getMessage());
            return false;
        }
    }

    private function normaliseResult(array $raw): array
    {
        $hits = [];
        foreach ($raw['hits'] ?? [] as $hit) {
            $hits[] = ['document' => $hit];
        }

        return [
            'hits'           => $hits,
            'found'          => $raw['estimatedTotalHits'] ?? $raw['totalHits'] ?? count($hits),
            'search_time_ms' => $raw['processingTimeMs'] ?? 0,
            'facet_counts'   => [],
        ];
    }

    private function normaliseMultiResult(array $raw): array
    {
        $allHits      = [];
        $totalFound   = 0;
        $searchTimeMs = 0;

        foreach ($raw['results'] ?? [] as $result) {
            foreach ($result['hits'] ?? [] as $hit) {
                $allHits[] = ['document' => $hit];
            }
            $totalFound  += (int)($result['estimatedTotalHits'] ?? $result['totalHits'] ?? 0);
            $searchTimeMs = max($searchTimeMs, (int)($result['processingTimeMs'] ?? 0));
        }

        return [
            'hits'           => $allHits,
            'found'          => $totalFound,
            'search_time_ms' => $searchTimeMs,
            'facet_counts'   => [],
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
        ]);
    }

    // ─── Bulk operations ──────────────────────────────────────────────────────

    /**
     * Bulk index documents. Groups by type and sends batches to Meilisearch.
     */
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
                $this->createOrUpdateCollection($type);
                $indexName = $this->getCollectionName($type);

                $result = $this->post("/indexes/{$indexName}/documents", $docs);
                if (($result['_code'] ?? 0) >= 400 && ($result['_code'] ?? 0) !== 202) {
                    error_log("Deepsearch::bulkIndex failed for '{$type}': " . json_encode($result));
                    $results[$type] = ['error' => json_encode($result)];
                } else {
                    $results[$type] = ['taskUid' => $result['taskUid'] ?? null, 'status' => 'enqueued'];
                }
            }
        }

        return $results;
    }

    // ─── Health ───────────────────────────────────────────────────────────────

    public function isHealthy(): bool
    {
        try {
            $response = $this->get('/health', 5);
            return ($response['status'] ?? '') === 'available';
        } catch (\Exception $e) {
            error_log("Deepsearch::isHealthy failed: " . $e->getMessage());
            return false;
        }
    }

    // ─── Raw HTTP access for external scripts ────────────────────────────────

    /**
     * Expose connection details for scripts that need raw HTTP access
     * (index_files.php, index_db.php, etc.)
     */
    public function getConnectionInfo(): array
    {
        return [
            'host'    => $this->host,
            'port'    => $this->port,
            'api_key' => $this->apiKey,
        ];
    }
}
