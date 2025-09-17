<?php
namespace Tribe;

use Typesense\Client;
use Typesense\Exceptions\TypesenseClientError;

class Typesense {
    private $client;
    private $config;
    
    public function __construct()
    {
        $this->config = new Config();
        
        $this->client = new Client([
            'api_key' => $_ENV['TYPESENSE_API_KEY'] ?? 'xyz',
            'nodes' => [
                [
                    'host' => $_ENV['PROJECT_NAME'].'_typesense',
                    'port' => $_ENV['TYPESENSE_INTERNAL_PORT'] ?? '8108',
                    'protocol' => $_ENV['TYPESENSE_PROTOCOL'] ?? 'http'
                ]
            ],
            'connection_timeout_seconds' => 30,
            'healthcheck_interval_seconds' => 2,
            'num_retries' => 3,
            'retry_interval_seconds' => 0.01,
        ]);
    }
    
    /**
     * Create or update a collection schema for a specific type
     */
    public function createOrUpdateCollection($type)
    {
        $types = $this->config->getTypes();
        
        if (!isset($types[$type])) {
            error_log("Type '{$type}' not found in configuration");
            return false;
        }
        
        $collectionName = $this->getCollectionName($type);
        $schema = $this->buildCollectionSchema($type, $types[$type]);
        
        try {
            // Try to get existing collection
            $existing = $this->client->collections[$collectionName]->retrieve();
            // Collection exists, check if schema needs updating
            return $this->updateCollectionIfNeeded($collectionName, $schema, $existing);
        } catch (TypesenseClientError $e) {
            if ($e->getCode() === 404) {
                // Collection doesn't exist, create new one
                try {
                    $result = $this->client->collections->create($schema);
                    return $result;
                } catch (TypesenseClientError $e) {
                    error_log("Failed to create collection '{$collectionName}': " . $e->getMessage());
                    return false;
                }
            } else {
                error_log("Error checking collection '{$collectionName}': " . $e->getMessage());
                return false;
            }
        }
    }
    
    /**
     * Build Typesense collection schema from type configuration
     */
    private function buildCollectionSchema($type, $typeConfig)
    {
        $fields = [
            // Core fields
            ['name' => 'id', 'type' => 'int64', 'facet' => false],
            ['name' => 'type', 'type' => 'string', 'facet' => true],
            ['name' => 'slug', 'type' => 'string', 'facet' => false],
            ['name' => 'content_privacy', 'type' => 'string', 'facet' => true],
            ['name' => 'created_on', 'type' => 'int64', 'facet' => false],
            ['name' => 'updated_on', 'type' => 'int64', 'facet' => false],
            ['name' => 'user_id', 'type' => 'int64', 'facet' => true, 'optional' => true],
            
            // Full-text search field
            ['name' => 'search_content', 'type' => 'string', 'facet' => false],
        ];
        
        // Add module-specific fields
        if (isset($typeConfig['modules'])) {
            foreach ($typeConfig['modules'] as $module) {
                $fieldName = $module['input_slug'];
                $fieldType = $this->mapInputTypeToTypesense($module['input_type'] ?? 'string');
                
                $field = [
                    'name' => $fieldName,
                    'type' => $fieldType,
                    'facet' => $this->shouldFieldBeFacet($module),
                    'optional' => true
                ];
                
                // Add indexing and search configuration
                if ($fieldType === 'string') {
                    $field['index'] = true;
                }
                
                $fields[] = $field;
            }
        }
        
        return [
            'name' => $this->getCollectionName($type),
            'fields' => $fields,
            'default_sorting_field' => 'updated_on',
            // Enable advanced search features
            'enable_nested_fields' => true,
            'token_separators' => ['-', '_', '.', '@'],
            'symbols_to_index' => ['@', '#'],
        ];
    }
    
    /**
     * Map input types to Typesense field types
     */
    private function mapInputTypeToTypesense($inputType)
    {
        $mapping = [
            'text' => 'string',
            'textarea' => 'string',
            'select' => 'string',
            'radio' => 'string',
            'checkbox' => 'bool',
            'number' => 'int64',
            'float' => 'float',
            'date' => 'int64',
            'email' => 'string',
            'url' => 'string',
            'file' => 'string',
            'image' => 'string',
            'password' => 'string',
        ];
        
        return $mapping[$inputType] ?? 'string';
    }
    
    /**
     * Determine if a field should be facetable
     */
    private function shouldFieldBeFacet($module)
    {
        $facetableTypes = ['select', 'radio', 'checkbox'];
        return in_array($module['input_type'] ?? '', $facetableTypes) || 
               ($module['list_field'] ?? false) === true;
    }
    
    /**
     * Get collection name for a type
     */
    private function getCollectionName($type)
    {
        return "tribe_{$type}";
    }
    
    /**
     * Index a single document
     */
    public function indexDocument($object)
    {
        if (!isset($object['type']) || !isset($object['id'])) {
            error_log("Invalid object for indexing: missing type or id");
            return false;
        }
        
        $collectionName = $this->getCollectionName($object['type']);
        
        // Ensure collection exists
        $this->createOrUpdateCollection($object['type']);
        
        $document = $this->transformObjectToDocument($object);
        
        try {
            $result = $this->client->collections[$collectionName]->documents->create($document);
            return $result;
        } catch (TypesenseClientError $e) {
            error_log("Failed to index document: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update a document
     */
    public function updateDocument($object)
    {
        if (!isset($object['type']) || !isset($object['id'])) {
            return false;
        }
        
        $collectionName = $this->getCollectionName($object['type']);
        $document = $this->transformObjectToDocument($object);
        
        try {
            $result = $this->client->collections[$collectionName]->documents[(string)$object['id']]->update($document);
            return $result;
        } catch (TypesenseClientError $e) {
            error_log("Failed to update document: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete a document
     */
    public function deleteDocument($id, $type)
    {
        $collectionName = $this->getCollectionName($type);
        
        try {
            $result = $this->client->collections[$collectionName]->documents[(string)$id]->delete();
            return $result;
        } catch (TypesenseClientError $e) {
            error_log("Failed to delete document: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Transform object to Typesense document
     */
    private function transformObjectToDocument($object)
    {
        $document = [
            'id' => (string)$object['id'],
            'type' => $object['type'],
            'slug' => $object['slug'] ?? '',
            'content_privacy' => $object['content_privacy'] ?? 'public',
            'created_on' => (int)($object['created_on'] ?? time()),
            'updated_on' => (int)($object['updated_on'] ?? time()),
        ];
        
        // Add user_id if present
        if (isset($object['user_id'])) {
            $document['user_id'] = (int)$object['user_id'];
        }
        
        // Build search content from searchable fields
        $searchContent = [];
        $types = $this->config->getTypes();
        
        if (isset($types[$object['type']]['modules'])) {
            foreach ($types[$object['type']]['modules'] as $module) {
                $fieldName = $module['input_slug'];
                
                if (isset($object[$fieldName]) && !empty($object[$fieldName])) {
                    $value = $object[$fieldName];
                    
                    // Add to document with proper type casting
                    $document[$fieldName] = $this->castFieldValue($value, $module['input_type'] ?? 'string');
                    
                    // Add searchable content to search_content field
                    if ($this->isFieldSearchable($module)) {
                        if (is_array($value)) {
                            $searchContent[] = implode(' ', array_filter($value, 'is_string'));
                        } else if (is_string($value)) {
                            $searchContent[] = $value;
                        }
                    }
                }
            }
        }
        
        // Create combined search content
        $document['search_content'] = implode(' ', array_filter($searchContent));
        
        return $document;
    }
    
    /**
     * Cast field value to appropriate type
     */
    private function castFieldValue($value, $inputType)
    {
        switch ($inputType) {
            case 'number':
                return (int)$value;
            case 'float':
                return (float)$value;
            case 'checkbox':
                return (bool)$value;
            case 'date':
                return is_numeric($value) ? (int)$value : strtotime($value);
            default:
                if (is_array($value)) {
                    return implode(', ', $value);
                }
                return (string)$value;
        }
    }
    
    /**
     * Check if field should be included in search content
     */
    private function isFieldSearchable($module)
    {
        $searchableTypes = ['text', 'textarea', 'select', 'radio'];
        return in_array($module['input_type'] ?? '', $searchableTypes);
    }
    
    /**
     * Perform search
     */
    public function search($query, $options = [])
    {
        $searchParams = [
            'q' => $query,
            'query_by' => $options['query_by'] ?? 'search_content',
            'per_page' => $options['per_page'] ?? 25,
            'page' => $options['page'] ?? 1,
        ];
        
        // Add type filter if specified
        if (!empty($options['type'])) {
            $searchParams['filter_by'] = "type:={$options['type']}";
        }
        
        // Add privacy filter
        if ($options['show_public_only'] ?? true) {
            $privacyFilter = 'content_privacy:=public';
            $searchParams['filter_by'] = isset($searchParams['filter_by']) 
                ? $searchParams['filter_by'] . ' && ' . $privacyFilter 
                : $privacyFilter;
        }
        
        // Add additional filters
        if (!empty($options['filters'])) {
            foreach ($options['filters'] as $field => $value) {
                $filter = is_array($value) ? "{$field}:[" . implode(',', $value) . "]" : "{$field}:={$value}";
                $searchParams['filter_by'] = isset($searchParams['filter_by']) 
                    ? $searchParams['filter_by'] . ' && ' . $filter 
                    : $filter;
            }
        }
        
        // Enable faceting
        if (!empty($options['facet_by'])) {
            $searchParams['facet_by'] = is_array($options['facet_by']) 
                ? implode(',', $options['facet_by']) 
                : $options['facet_by'];
        }
        
        // Sorting
        if (!empty($options['sort_by'])) {
            $searchParams['sort_by'] = $options['sort_by'];
        }
        
        // Highlighting
        $searchParams['highlight_full_fields'] = 'search_content';
        $searchParams['highlight_affix_num_tokens'] = 3;
        
        // Typo tolerance
        $searchParams['num_typos'] = $options['num_typos'] ?? 2;
        
        try {
            if (empty($options['type'])) {
                // Multi-collection search
                $types = array_keys($this->config->getTypes());
                $searches = [];
                
                foreach ($types as $type) {
                    if ($type === 'webapp') continue; // Skip webapp type
                    
                    $searchRequest = [
                        'collection' => $this->getCollectionName($type),
                        'q' => $query,
                        'query_by' => 'search_content',
                    ];
                    
                    if (isset($searchParams['filter_by'])) {
                        $searchRequest['filter_by'] = $searchParams['filter_by'];
                    }
                    
                    $searches[] = $searchRequest;
                }
                
                return $this->client->multiSearch->perform(['searches' => $searches]);
            } else {
                // Single collection search
                $collectionName = $this->getCollectionName($options['type']);
                return $this->client->collections[$collectionName]->documents->search($searchParams);
            }
        } catch (TypesenseClientError $e) {
            error_log("Search failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get suggestions for autocomplete
     */
    public function getSuggestions($query, $type = null, $limit = 10)
    {
        $options = [
            'per_page' => $limit,
            'query_by' => 'search_content',
            'prefix' => true,
        ];
        
        if ($type) {
            $options['type'] = $type;
        }
        
        return $this->search($query, $options);
    }
    
    /**
     * Bulk index documents
     */
    public function bulkIndex($objects, $batchSize = 100)
    {
        $results = [];
        $batches = array_chunk($objects, $batchSize);
        
        foreach ($batches as $batch) {
            $collectionsByType = [];
            
            // Group by type
            foreach ($batch as $object) {
                if (!isset($object['type'])) continue;
                
                $type = $object['type'];
                if (!isset($collectionsByType[$type])) {
                    $collectionsByType[$type] = [];
                    // Ensure collection exists
                    $this->createOrUpdateCollection($type);
                }
                
                $collectionsByType[$type][] = $this->transformObjectToDocument($object);
            }
            
            // Bulk import each type
            foreach ($collectionsByType as $type => $typeDocuments) {
                $collectionName = $this->getCollectionName($type);
                
                try {
                    $result = $this->client->collections[$collectionName]->documents->import($typeDocuments, ['action' => 'upsert']);
                    $results[$type] = $result;
                } catch (TypesenseClientError $e) {
                    error_log("Bulk import failed for type '{$type}': " . $e->getMessage());
                    $results[$type] = ['error' => $e->getMessage()];
                }
            }
        }
        
        return $results;
    }
    
    /**
     * Health check
     */
    public function isHealthy()
    {
        try {
            $response = $this->client->health->retrieve();
            return isset($response['ok']) && $response['ok'] === true;
        } catch (TypesenseClientError $e) {
            error_log("Health check failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update collection schema if needed
     */
    private function updateCollectionIfNeeded($collectionName, $newSchema, $existingCollection)
    {
        try {
            // Compare fields - simplified comparison
            $existingFields = array_column($existingCollection['fields'] ?? [], 'name');
            $newFields = array_column($newSchema['fields'], 'name');
            
            $missingFields = array_diff($newFields, $existingFields);
            
            if (!empty($missingFields)) {
                // Add missing fields
                foreach ($newSchema['fields'] as $field) {
                    if (in_array($field['name'], $missingFields)) {
                        $this->client->collections[$collectionName]->fields->create($field);
                    }
                }
            }
            
            return true;
        } catch (TypesenseClientError $e) {
            error_log("Failed to update collection schema: " . $e->getMessage());
            return false;
        }
    }
}