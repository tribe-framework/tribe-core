<?php
namespace Tribe;

class Typesense {
    private string $host;
    private string $apiKey;
    private string $projectPrefix;

    public function __construct()
    {
        $this->host = 'http://' . ($_ENV['PROJECT_NAME'] ?? 'tribe') . '_typesense:8108';
        $this->apiKey = $_ENV['TYPESENSE_API_KEY'] ?? 'xyz';
        $this->projectPrefix = ($_ENV['PROJECT_NAME'] ?? 'tribe') . '_';
    }

    /**
     * Derive the Typesense collection name from an object's type,
     * prefixed by PROJECT_NAME to avoid cross-project collisions.
     */
    private function collectionName(string $type): string
    {
        return $this->projectPrefix . $type;
    }

    /**
     * Send an HTTP request to the Typesense server.
     *
     * @return array|null Decoded JSON response, or null on failure
     */
    private function request(string $method, string $path, ?array $body = null): ?array
    {
        $url = $this->host . $path;

        $opts = [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'X-TYPESENSE-API-KEY: ' . $this->apiKey,
            ],
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_CONNECTTIMEOUT => 3,
        ];

        if ($body !== null) {
            $opts[CURLOPT_POSTFIELDS] = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $ch = curl_init();
        curl_setopt_array($ch, $opts);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            error_log("[Typesense] cURL error: {$error}");
            return null;
        }

        $decoded = json_decode($response, true);

        if ($httpCode >= 400) {
            $msg = $decoded['message'] ?? $response;
            error_log("[Typesense] HTTP {$httpCode} on {$method} {$path}: {$msg}");
        }

        return $decoded;
    }

    /**
     * Ensure a collection exists for the given type.
     * Uses an auto-schema collection so any JSON fields are accepted.
     */
    private function ensureCollection(string $type): void
    {
        $collection = $this->collectionName($type);
        $check = $this->request('GET', "/collections/{$collection}");

        // Collection already exists
        if ($check && isset($check['name'])) {
            return;
        }

        // Create with auto-schema — Typesense will detect fields from the first document
        $schema = [
            'name'                  => $collection,
            'enable_nested_fields'  => true,
            'fields'                => [
                ['name' => '.*', 'type' => 'auto'],
            ],
        ];

        $result = $this->request('POST', '/collections', $schema);

        if ($result && isset($result['name'])) {
            error_log("[Typesense] Created collection: {$collection}");
        }
    }

    /**
     * Flatten an object into a Typesense-safe document.
     * Converts the object's full JSON representation, ensuring `id` is a string
     * (Typesense requirement) and stripping null values.
     */
    private function toDocument(array $object): array
    {
        $doc = $object;

        // Typesense requires `id` to be a string
        if (isset($doc['id'])) {
            $doc['id'] = (string) $doc['id'];
        }

        // Remove null values — Typesense rejects them
        return array_filter($doc, fn($v) => $v !== null);
    }

    // ─── Public CRUD Methods ─────────────────────────────────────────────

    /**
     * Index (upsert) an object into Typesense.
     * Called on both create (POST) and update (PATCH).
     */
    public function upsert(array $object): void
    {
        if (empty($object['type']) || empty($object['id'])) {
            return;
        }

        $this->ensureCollection($object['type']);

        $collection = $this->collectionName($object['type']);
        $doc = $this->toDocument($object);

        $this->request('POST', "/collections/{$collection}/documents?action=upsert", $doc);
    }

    /**
     * Remove an object from Typesense.
     * Requires type and id; fetches the object first if type is unknown.
     */
    public function delete(string $type, int $id): void
    {
        if (empty($type) || !$id) {
            return;
        }

        $collection = $this->collectionName($type);
        $this->request('DELETE', "/collections/{$collection}/documents/{$id}");
    }

    // ─── Search Methods ──────────────────────────────────────────────────

    /**
     * Search a single collection by type.
     *
     * Supports all Typesense search parameters: query_by, filter_by,
     * sort_by, facet_by, group_by, group_limit, page, per_page,
     * prefix, infix, highlight_fields, highlight_full_fields,
     * include_fields, exclude_fields, pinned_hits, hidden_hits,
     * enable_overrides, pre_segmented_query, exhaustive_search,
     * drop_tokens_threshold, typo_tokens_threshold, num_typos,
     * max_facet_values, etc.
     *
     * @param string $type       The object type (maps to collection)
     * @param string $q          The search query string ('*' for match-all)
     * @param array  $options    Typesense search parameters
     * @return array             Raw Typesense response with added metadata
     */
    public function search(string $type, string $q, array $options = []): array
    {
        $collection = $this->collectionName($type);

        // Build query parameters
        $queryParams = $this->buildSearchParams($q, $options, $type);

        $path = "/collections/{$collection}/documents/search?" . http_build_query($queryParams);
        $result = $this->request('GET', $path);

        if ($result === null) {
            return $this->emptySearchResult($q, $type);
        }

        // Normalize the response with metadata
        return $this->formatSearchResult($result, $q, $type);
    }

    /**
     * Search across ALL project collections in parallel via multi_search.
     * Automatically discovers all collections prefixed with PROJECT_NAME.
     *
     * @param string $q       The search query
     * @param array  $options Shared search parameters applied to every collection
     * @return array          Merged and de-duplicated results across collections
     */
    public function searchAllCollections(string $q, array $options = []): array
    {
        $collections = $this->listCollections();

        if (empty($collections)) {
            return $this->emptySearchResult($q, null);
        }

        // Build individual searches for each collection
        $searches = [];
        foreach ($collections as $col) {
            $type = $col['type'] ?? str_replace($this->projectPrefix, '', $col['name']);
            $search = [
                'collection' => $col['name'],
                'q'          => $q,
            ];

            // Apply shared options
            $params = $this->buildSearchParams($q, $options, $type);
            unset($params['q']); // already set above
            $search = array_merge($search, $params);

            $searches[] = $search;
        }

        $body = ['searches' => $searches];
        $result = $this->request('POST', '/multi_search', $body);

        if ($result === null || !isset($result['results'])) {
            return $this->emptySearchResult($q, null);
        }

        return $this->mergeMultiSearchResults($result['results'], $q, $collections);
    }

    /**
     * Execute a multi-search request with custom per-search parameters.
     * Each element of $searches should have at minimum 'collection' and 'q'.
     *
     * @param array $searches      Array of search objects
     * @param array $commonParams  Parameters applied to all searches
     * @return array               Raw Typesense multi_search response
     */
    public function multiSearch(array $searches, array $commonParams = []): array
    {
        // Prefix collection names with project prefix if not already
        foreach ($searches as &$search) {
            if (isset($search['collection']) && strpos($search['collection'], $this->projectPrefix) !== 0) {
                $search['collection'] = $this->collectionName($search['collection']);
            }
            // Also accept 'type' as alias for 'collection'
            if (!isset($search['collection']) && isset($search['type'])) {
                $search['collection'] = $this->collectionName($search['type']);
                unset($search['type']);
            }
        }
        unset($search);

        $body = ['searches' => $searches];

        // Common params go as query string
        $queryString = !empty($commonParams) ? '?' . http_build_query($commonParams) : '';
        $result = $this->request('POST', '/multi_search' . $queryString, $body);

        if ($result === null) {
            return ['results' => []];
        }

        return $result;
    }

    /**
     * List all Typesense collections belonging to this project.
     *
     * @return array  Array of collection metadata (name, num_documents, type, fields)
     */
    public function listCollections(): array
    {
        $all = $this->request('GET', '/collections');

        if ($all === null || !is_array($all)) {
            return [];
        }

        $projectCollections = [];
        foreach ($all as $col) {
            // Only return collections belonging to this project
            if (isset($col['name']) && strpos($col['name'], $this->projectPrefix) === 0) {
                $type = str_replace($this->projectPrefix, '', $col['name']);
                $projectCollections[] = [
                    'name'           => $col['name'],
                    'type'           => $type,
                    'num_documents'  => $col['num_documents'] ?? 0,
                    'fields'         => $col['fields'] ?? [],
                    'created_at'     => $col['created_at'] ?? null,
                ];
            }
        }

        return $projectCollections;
    }

    // ─── Private Search Helpers ──────────────────────────────────────────

    /**
     * Build the Typesense query parameter array from options.
     * Automatically resolves query_by from collection schema if not provided.
     */
    private function buildSearchParams(string $q, array $options, ?string $type = null): array
    {
        $params = ['q' => $q];

        // If query_by is not specified, auto-detect string fields from the collection schema
        $queryBy = $options['query_by'] ?? null;
        if (empty($queryBy) && $type) {
            $queryBy = $this->detectQueryByFields($type);
        }
        if (!empty($queryBy)) {
            $params['query_by'] = $queryBy;
        }

        // Default highlight context window to 50 tokens (overridable via options)
        if (!isset($options['highlight_affix_num_tokens'])) {
            $params['highlight_affix_num_tokens'] = 50;
        }

        // Map all supported Typesense search parameters
        $typesenseParams = [
            'filter_by', 'sort_by', 'facet_by',
            'group_by', 'group_limit',
            'page', 'per_page',
            'prefix', 'infix',
            'highlight_fields', 'highlight_full_fields',
            'highlight_affix_num_tokens', 'highlight_start_tag', 'highlight_end_tag',
            'include_fields', 'exclude_fields',
            'pinned_hits', 'hidden_hits',
            'enable_overrides',
            'pre_segmented_query',
            'exhaustive_search',
            'drop_tokens_threshold', 'typo_tokens_threshold',
            'num_typos',
            'max_facet_values',
            'facet_query',
            'prioritize_exact_match',
            'prioritize_token_position',
            'search_cutoff_ms',
            'use_cache',
            'cache_ttl',
            'vector_query',
            'voice_query',
            'text_match_type',
            'split_join_tokens',
            'min_len_1typo', 'min_len_2typo',
            'enable_typos_for_numerical_tokens',
            'enable_typos_for_alpha_numerical_tokens',
            'enable_lazy_filter',
            'remote_embedding_timeout_ms',
            'remote_embedding_num_tries',
        ];

        foreach ($typesenseParams as $param) {
            if (isset($options[$param]) && $options[$param] !== null && $options[$param] !== '') {
                $params[$param] = $options[$param];
            }
        }

        return $params;
    }

    /**
     * Auto-detect searchable string/auto fields from a collection's schema.
     * Falls back to a wildcard if the collection doesn't exist or has only auto fields.
     */
    private function detectQueryByFields(string $type): string
    {
        $collection = $this->collectionName($type);
        $schema = $this->request('GET', "/collections/{$collection}");

        if (!$schema || !isset($schema['fields'])) {
            // Fallback: common Tribe object fields
            return 'title,name,slug,content,description,body';
        }

        $stringFields = [];
        foreach ($schema['fields'] as $field) {
            $name = $field['name'] ?? '';
            $fieldType = $field['type'] ?? '';

            // Skip internal fields and the wildcard auto-field
            if ($name === '.*' || strpos($name, '.') === 0) {
                continue;
            }

            // Accept string and string[] fields as searchable
            if (in_array($fieldType, ['string', 'string[]', 'auto'], true)) {
                $stringFields[] = $name;
            }
        }

        // If we only have auto-schema ('.*'), fall back to common field names
        if (empty($stringFields)) {
            return 'title,name,slug,content,description,body';
        }

        return implode(',', $stringFields);
    }

    /**
     * Format a single-collection Typesense search result into a consistent response.
     */
    private function formatSearchResult(array $raw, string $q, ?string $type): array
    {
        $hits = [];
        foreach ($raw['hits'] ?? [] as $hit) {
            $doc = $hit['document'] ?? [];
            $highlights = $this->extractHighlights($hit);

            $hits[] = [
                'document'        => $doc,
                'highlights'      => $highlights,
                'text_match'      => $hit['text_match'] ?? 0,
                'text_match_info' => $hit['text_match_info'] ?? null,
            ];
        }

        $result = [
            'success'         => true,
            'query'           => $q,
            'collection'      => $type,
            'found'           => $raw['found'] ?? 0,
            'out_of'          => $raw['out_of'] ?? 0,
            'page'            => $raw['page'] ?? 1,
            'search_time_ms'  => $raw['search_time_ms'] ?? 0,
            'hits'            => $hits,
        ];

        // Include facet counts if present
        if (!empty($raw['facet_counts'])) {
            $result['facet_counts'] = $raw['facet_counts'];
        }

        // Include grouped hits if present
        if (!empty($raw['grouped_hits'])) {
            $result['grouped_hits'] = $raw['grouped_hits'];
        }

        // Include request params for debugging
        if (isset($raw['request_params'])) {
            $result['request_params'] = $raw['request_params'];
        }

        return $result;
    }

    /**
     * Merge results from a multi-collection search into a unified response.
     * Sorts all hits by text_match score descending across collections.
     */
    private function mergeMultiSearchResults(array $results, string $q, array $collections): array
    {
        $allHits = [];
        $totalFound = 0;
        $totalSearchTimeMs = 0;
        $perCollectionMeta = [];

        foreach ($results as $i => $result) {
            $colName = $collections[$i]['name'] ?? 'unknown';
            $colType = $collections[$i]['type'] ?? str_replace($this->projectPrefix, '', $colName);

            $found = $result['found'] ?? 0;
            $totalFound += $found;
            $totalSearchTimeMs = max($totalSearchTimeMs, $result['search_time_ms'] ?? 0);

            $perCollectionMeta[] = [
                'collection' => $colType,
                'found'      => $found,
            ];

            foreach ($result['hits'] ?? [] as $hit) {
                $doc = $hit['document'] ?? [];
                $doc['_collection_type'] = $colType; // tag each hit with its source collection

                $highlights = $this->extractHighlights($hit);

                $allHits[] = [
                    'document'   => $doc,
                    'highlights' => $highlights,
                    'text_match' => $hit['text_match'] ?? 0,
                ];
            }
        }

        // Sort all hits by text_match score descending (best matches first)
        usort($allHits, fn($a, $b) => ($b['text_match'] ?? 0) <=> ($a['text_match'] ?? 0));

        return [
            'success'              => true,
            'query'                => $q,
            'collection'           => null,
            'found'                => $totalFound,
            'search_time_ms'       => $totalSearchTimeMs,
            'hits'                 => $allHits,
            'collection_breakdown' => $perCollectionMeta,
        ];
    }

    /**
     * Normalise Typesense highlight data from a single hit.
     *
     * Typesense returns highlights in two shapes depending on whether the
     * matched field is top-level or nested:
     *   - `highlights` (array)  — top-level field matches, each element has a `field` key
     *   - `highlight`  (object) — nested/dot-path field matches, keyed by field path
     *
     * Both are merged here so callers never miss `<mark>` snippets from nested fields.
     */
    private function extractHighlights(array $hit): array
    {
        $highlights = [];

        // Top-level field matches (array of highlight objects)
        foreach ($hit['highlights'] ?? [] as $hl) {
            $highlights[$hl['field'] ?? ''] = [
                'snippet'        => $hl['snippet'] ?? null,
                'snippets'       => $hl['snippets'] ?? null,
                'matched_tokens' => $hl['matched_tokens'] ?? [],
            ];
        }

        // Nested field matches — Typesense returns these as a recursive map where
        // intermediate keys are object names and leaf nodes carry snippet/matched_tokens.
        // Flatten to dot-path keys (e.g. "transcription" → "transcription.text").
        $this->flattenHighlightMap($hit['highlight'] ?? [], '', $highlights);

        return $highlights;
    }

    /**
     * Recursively flatten Typesense's nested `highlight` map into dot-path keyed entries.
     *
     * Typesense structures nested highlights as recursive objects rather than flat dot-paths:
     *   {"transcription": {"text": {"snippet": "...<mark>...</mark>", "matched_tokens": [...]}}}
     *
     * This method walks the tree, accumulating the path, and writes leaf nodes
     * (those containing a `snippet` or `matched_tokens` key) into $out using their
     * full dot-path as the key — skipping any path already populated by `highlights`.
     */
    private function flattenHighlightMap(array $map, string $prefix, array &$out): void
    {
        foreach ($map as $key => $value) {
            $path = $prefix === "" ? $key : "{$prefix}.{$key}";

            if (!is_array($value)) {
                continue;
            }

            // A leaf node has `snippet` or `matched_tokens` directly on it
            if (array_key_exists('snippet', $value) || array_key_exists('matched_tokens', $value)) {
                if (!isset($out[$path])) {
                    $out[$path] = [
                        'snippet'        => $value['snippet'] ?? null,
                        'snippets'       => $value['snippets'] ?? null,
                        'matched_tokens' => $value['matched_tokens'] ?? [],
                    ];
                }
            } else {
                // Intermediate node — recurse deeper
                $this->flattenHighlightMap($value, $path, $out);
            }
        }
    }

        /**
     * Return an empty search result structure.
     */
    private function emptySearchResult(string $q, ?string $type): array
    {
        return [
            'success'        => true,
            'query'          => $q,
            'collection'     => $type,
            'found'          => 0,
            'out_of'         => 0,
            'page'           => 1,
            'search_time_ms' => 0,
            'hits'           => [],
        ];
    }
}