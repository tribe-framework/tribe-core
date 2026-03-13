# tribe-core

A PHP-based headless CMS framework providing a JSON API for dynamic content management with flexible content types, authentication, and file handling.

## Dockerfile

The /config/Dockerfile is the one pushed to docker using `docker push tribeframework/tribe-core:latest`

## Compatibility

- PHP 8.0 or above
- MySQL 9.x
- Composer for dependency management

## Installation

1. Install via Composer

```bash
composer require tribe-framework/tribe-core
```

2. Configure environment variables in `.env`:

```env
DB_HOST=localhost
DB_PORT=3306
DB_NAME=your_database
DB_USER=your_user
DB_PASS=your_password
SSL=true
TRIBE_API_SECRET_KEY=your_secret_key
```

3. Initialize database

```sql
CREATE TABLE `data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`content`)),
  `type` varchar(255) DEFAULT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `content_privacy` enum('public','private','draft','pending','sent') DEFAULT 'public',
  `user_id` int(11) DEFAULT NULL,
  `role_slug` varchar(50) DEFAULT NULL,
  `created_on` int(11) DEFAULT NULL,
  `updated_on` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `type` (`type`),
  KEY `slug` (`slug`),
  KEY `content_privacy` (`content_privacy`),
  KEY `user_id` (`user_id`),
  KEY `created_on` (`created_on`),
  KEY `updated_on` (`updated_on`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Core Architecture

### Single-Table Design

Tribe uses a unified `data` table with JSON-based content storage:

- **id** - Auto-increment primary key
- **content** - JSON field containing all object attributes
- **type** - Content type identifier (e.g., 'post', 'user', 'file')
- **slug** - URL-friendly unique identifier
- **content_privacy** - Access level: public, private, draft, pending, sent
- **user_id** - Owner/creator reference
- **role_slug** - User role identifier
- **created_on** / **updated_on** - Unix timestamps

### Core Classes

```
\Tribe\Core      - Object CRUD and search operations
\Tribe\API       - RESTful JSON API handler
\Tribe\Config    - Content type definitions and configuration
\Tribe\MySQL     - Database abstraction layer
\Tribe\Uploads   - File upload and image processing
```

## Configuration & Content Types

### Content Type Definition

Content types are defined in JSON format and can be:

- Stored locally: `/config/types.json`
- Uploaded dynamically: `/uploads/types/*.json`
- Loaded from remote: GitHub repository blueprints

### Type Structure

```json
{
  "post": {
    "slug": "post",
    "title": "Posts",
    "sendable": false,
    "modules": [
      {
        "input_slug": "title",
        "input_type": "text",
        "input_primary": true,
        "input_unique": true,
        "input_placeholder": "Post Title"
      },
      {
        "input_slug": "body",
        "input_type": "textarea",
        "input_placeholder": "Post Content"
      },
      {
        "input_slug": "author_id",
        "input_type": "number",
        "linked_type": "user"
      }
    ]
  }
}
```

### Module Properties

- **input_slug** - Field identifier (required)
- **input_type** - Field type: text, textarea, number, select, file, etc.
- **input_primary** - Mark as primary/title field (one per type)
- **input_unique** - Enforce uniqueness across type
- **input_placeholder** - UI label/hint
- **var_type** - Data type: int, float, bool, string
- **linked_type** - Reference another content type
- **input_lang** - Multi-language field configuration
- **list_field** - Display in list views
- **input_options** - For select/radio fields

### Auto-Generated Fields

Every content type (except 'webapp') automatically includes:

**For Regular Types:**

```json
{
  "input_slug": "content_privacy",
  "input_options": [
    { "slug": "public", "title": "Public link" },
    { "slug": "private", "title": "Private link" },
    { "slug": "pending", "title": "Submit for moderation" },
    { "slug": "draft", "title": "Draft" }
  ]
}
```

**For Sendable Types:**

```json
{
  "input_slug": "content_privacy",
  "input_options": [
    { "slug": "sent", "title": "Send now" },
    { "slug": "draft", "title": "Save draft" }
  ]
}
```

### Configuration Methods

```php
$config = new \Tribe\Config();

// Get all content types
$types = $config->getTypes();

// Get type schema (all field slugs)
$schema = $config->getTypeSchema('post');

// Get primary module for a type
$primary = $config->getTypePrimaryModule('post', $types);

// Get linked modules (relationships)
$linked = $config->getTypeLinkedModules('post');

// Get project root directory
$root = $config->projectRoot();
```

## Core Class - Object Management

### Creating/Updating Objects

```php
$core = new \Tribe\Core();

// Create new object
$post = [
    'type' => 'post',
    'title' => 'My First Post',
    'body' => 'This is the content.',
    'content_privacy' => 'public'
];
$postId = $core->pushObject($post);

// Update existing object
$post = [
    'id' => $postId,
    'type' => 'post',
    'title' => 'Updated Title'  // Only updates provided fields
];
$core->pushObject($post);

// Overwrite entire object
$post = [
    'id' => $postId,
    'type' => 'post',
    'title' => 'New Title',
    'body' => 'New content'
];
$core->pushObject($post, true);  // Second parameter overwrites
```

### Batch Creating/Updating Objects

```php
// pushObjects - Batch insert/update multiple objects efficiently
// Reduces SQL queries by batching operations instead of individual pushObject calls

// Create multiple new objects
$posts = [
    [
        'type' => 'post',
        'title' => 'First Post',
        'body' => 'Content of first post',
        'content_privacy' => 'public'
    ],
    [
        'type' => 'post',
        'title' => 'Second Post',
        'body' => 'Content of second post',
        'content_privacy' => 'public'
    ],
    [
        'type' => 'post',
        'title' => 'Third Post',
        'body' => 'Content of third post',
        'content_privacy' => 'draft'
    ]
];

$ids = $core->pushObjects($posts);
// Returns: [123, 124, 125] - Array of IDs in same order as input

// Update existing objects in batch
$updates = [
    ['id' => 123, 'type' => 'post', 'title' => 'Updated First'],
    ['id' => 124, 'type' => 'post', 'title' => 'Updated Second'],
    ['id' => 125, 'type' => 'post', 'content_privacy' => 'public']
];

$ids = $core->pushObjects($updates);
// Only updates provided fields for each object

// Overwrite entire objects (replace all fields)
$replacements = [
    ['id' => 123, 'type' => 'post', 'title' => 'New Title', 'body' => 'New body'],
    ['id' => 124, 'type' => 'post', 'title' => 'Another Title', 'body' => 'Another body']
];

$ids = $core->pushObjects($replacements, true);  // Second parameter overwrites

// Custom chunk size for very large batches
$largeDataset = [/* 1000+ objects */];
$ids = $core->pushObjects($largeDataset, false, 1000);  // Chunk size of 1000

// Mixed new and existing objects
$mixed = [
    ['type' => 'post', 'title' => 'New Post'],           // No ID - will create
    ['id' => 50, 'type' => 'post', 'title' => 'Update'], // Has ID - will update
    ['type' => 'post', 'title' => 'Another New']         // No ID - will create
];

$ids = $core->pushObjects($mixed);
// Returns IDs maintaining input order
```

**Performance Benefits:**

```php
// BAD: Individual operations (N queries for N objects)
foreach ($posts as $post) {
    $core->pushObject($post);
}

// GOOD: Batch operation (2-3 queries total regardless of N)
$core->pushObjects($posts);

// Example: 500 objects
// pushObject in loop: ~1000 queries (INSERT + UPDATE per object)
// pushObjects: 3-4 queries (batched INSERTs and UPDATEs)
```

**Parameters:**

- **$posts** (array): Array of post arrays in same format as pushObject
- **$overwrite_posts** (bool, default: false): When true, replaces entire object instead of merging
- **$chunk_size** (int, default: 500): Number of rows to batch per SQL statement

**Return Value:**

- Array of resulting IDs in the same order as input posts

**Notes:**

- All standard pushObject features apply: type casting, slug generation, uniqueness checks
- Syncs to Typesense automatically (when enabled)
- Handles mixed new and existing objects seamlessly
- Use for bulk imports, migrations, or batch updates

### Reading Objects

```php
// Get single object by ID
$post = $core->getObject(123);
echo $post['title'];
echo $post['created_on'];

// Get specific attribute
$title = $core->getAttribute(123, 'title');

// Get multiple objects by IDs
$posts = $core->getObjects([1, 2, 3, 4, 5]);
foreach ($posts as $post) {
    echo $post['title'];
}
```

### Deleting Objects

```php
// Delete single object
$core->deleteObject(123);

// Delete multiple objects
$core->deleteObjects([1, 2, 3, 4, 5]);
```

### Updating Single Attributes

```php
// Update specific field
$core->pushAttribute(123, 'title', 'New Title');

// Useful for quick updates without loading full object
$core->pushAttribute(123, 'view_count', 150);
```

### Slug Generation

```php
// Generate slug from string
$slug = $core->slugify('My Post Title');
// Result: my-post-title-5f8a3c2b1

// Generate slug without unique ID (when field is unique)
$slug = $core->slugify('My Post Title', true);
// Result: my-post-title
```

### Search Operations

**Basic Search:**

```php
// Search for posts
$searchArray = [
    'type' => 'post',
    'title' => 'Tribe'
];

$ids = $core->getIDs(
    $searchArray,           // Search criteria
    "0, 10",               // Limit (offset, count)
    'created_on',          // Sort field
    'DESC',                // Sort order
    true                   // Show public only
);

// Get objects from IDs
$posts = $core->getObjects($ids);
```

**Advanced Search with Parameters:**

```php
$ids = $core->getIDs(
    search_arr: [
        'type' => 'post',
        'category' => 'tech',
        'status' => 'published'
    ],
    limit: "20, 10",                      // Skip 20, get 10
    sort_field: ['created_on', 'title'],  // Multiple sort
    sort_order: ['DESC', 'ASC'],
    show_public_objects_only: false,      // Include all privacy levels
    ignore_ids: [5, 10, 15],             // Exclude specific IDs
    show_partial_search_results: true,    // Enable LIKE search
    show_case_sensitive_search_results: false,
    comparison_within_module_phrase: 'LIKE',
    inbetween_same_module_phrases: 'OR',
    between_different_module_phrases: 'AND'
);
```

**Search with Array Values (OR conditions):**

```php
// Find posts with multiple categories
$ids = $core->getIDs([
    'type' => 'post',
    'category' => ['tech', 'science', 'programming']  // OR condition
]);
```

**Range Search:**

```php
// Find posts within date range
$ids = $core->getIDs(
    search_arr: ['type' => 'post'],
    range: [
        'created_on' => [
            'from' => 1640000000,
            'to' => 1650000000
        ],
        'view_count' => [
            'from' => 100
        ]
    ]
);
```

**Random Ordering:**

```php
// Get random posts
$ids = $core->getIDs(
    search_arr: ['type' => 'post'],
    limit: "0, 5",
    sort_field: '(random)'
);
```

**Get Total Count:**

```php
$totalCount = $core->getIDsTotalCount(
    search_arr: ['type' => 'post', 'status' => 'published'],
    limit: "0, 10",
    sort_field: 'created_on',
    sort_order: 'DESC'
);
```

**Full-Text Database Search:**

```php
// Fallback search when advanced search is unavailable
$results = $core->searchDatabase(
    query: 'machine learning tutorial',
    options: [
        'type' => 'post',
        'limit' => '0, 20',
        'sort_field' => 'created_on',
        'sort_order' => 'DESC',
        'show_public_objects_only' => true
    ]
);

// Returns array with:
// - objects: Array of matching objects
// - total_found: Total count
// - search_time_ms: Query execution time
// - source: 'database'
```

## API Class - RESTful Interface

### Basic Usage

```php
// api.php
require __DIR__ . '/_init.php';
$api = new \Tribe\API;
$api->jsonAPI('1.1');
```

### Endpoint Structure

The API uses a simplified path structure without `/api/v1.1/` prefix:

```
GET    /api.php/{type}           - List objects
GET    /api.php/{type}/{id}      - Get single object by ID
GET    /api.php/{type}/{slug}    - Get single object by slug
POST   /api.php/{type}           - Create object
PATCH  /api.php/{type}/{id}      - Update object
DELETE /api.php/{type}/{id}      - Delete object
```

**Examples:**

```
GET https://tribe.yourwebsitelink.com/api.php/petition/108820
GET https://tribe.yourwebsitelink.com/api.php/webapp/0
GET https://tribe.yourwebsitelink.com/api.php/post
POST https://tribe.yourwebsitelink.com/api.php/post
```

### Authentication Levels

**1. Junction/Tribe Domains (Full Access)**

- Requests from trusted internal domains (defined in `$_ENV['BARE_URL']`)
- Full CRUD operations
- No API key required
- Example: Requests from `tribe.yourwebsitelink.com` to itself

**2. API Key with Full Access**

```
Authorization: Bearer YOUR_FULL_ACCESS_KEY
```

- Complete read/write operations
- Can create, update, delete
- Configured in database as `apikey_record` type with `readonly=false`

**3. API Key with Read Access**

```
Authorization: Bearer YOUR_READ_ONLY_KEY
```

- GET operations only
- Cannot create, update, or delete
- Configured in database as `apikey_record` type with `readonly=true`

**4. Development Mode**

```
Authorization: Bearer YOUR_API_KEY
```

- API key with `devmode=true` flag
- Full access when request originates from localhost/127.0.0.1
- Useful for local development

**5. Domain Whitelisting**

- API keys can have `whitelisted_domains` field
- Supports wildcard patterns (e.g., `*.example.com`)
- Restricts API key usage to specific domains
- Format: One domain per line in the field

**6. Public Access**

- No authentication required
- Access to `content_privacy='public'` objects only
- GET operations on public content

### Creating API Keys

API keys are stored as objects in the database with type `apikey_record`:

```php
$core = new \Tribe\Core();

// Create read-only API key
$apiKey = [
    'type' => 'apikey_record',
    'apikey' => bin2hex(random_bytes(32)), // Generate secure random key
    'readonly' => true,
    'content_privacy' => 'private', // or 'public'
    'whitelisted_domains' => "example.com\n*.myapp.com", // Optional
    'devmode' => false
];
$keyId = $core->pushObject($apiKey);

// Create full-access API key
$fullAccessKey = [
    'type' => 'apikey_record',
    'apikey' => bin2hex(random_bytes(32)),
    'readonly' => false,
    'content_privacy' => 'private',
    'whitelisted_domains' => "admin.example.com"
];
$fullKeyId = $core->pushObject($fullAccessKey);

// Create development API key
$devKey = [
    'type' => 'apikey_record',
    'apikey' => bin2hex(random_bytes(32)),
    'readonly' => false,
    'devmode' => true, // Full access from localhost only
    'content_privacy' => 'private'
];
$devKeyId = $core->pushObject($devKey);
```

### Request Examples

**List Objects:**

```bash
# Basic list
GET /api.php/post

# With filtering
GET /api.php/post?filter[status]=published&filter[category]=tech

# With pagination
GET /api.php/post?page[offset]=20&page[limit]=10

# With sorting (- prefix for descending)
GET /api.php/post?sort=-created_on,title

# Show all (requires auth)
GET /api.php/post?show_public_objects_only=false
```

**Get Single Object by ID:**

```bash
GET /api.php/post/123
```

**Get Single Object by Slug:**

```bash
GET /api.php/post/my-post-title-abc123
```

**Get Types Configuration (webapp/0):**

```bash
GET /api.php/webapp/0
```

Returns all content type definitions, statistics, and configuration.

**Create Object:**

```bash
POST /api.php/post
Content-Type: application/vnd.api+json
Authorization: Bearer YOUR_API_KEY

{
  "data": {
    "type": "post",
    "attributes": {
      "modules": {
        "title": "My Post",
        "body": "Content here",
        "category": "tech"
      }
    }
  }
}
```

**Update Object:**

```bash
PATCH /api.php/post/123
Content-Type: application/vnd.api+json
Authorization: Bearer YOUR_API_KEY

{
  "data": {
    "type": "post",
    "id": "123",
    "attributes": {
      "modules": {
        "title": "Updated Title"
      }
    }
  }
}
```

**Delete Object:**

```bash
DELETE /api.php/post/123
Authorization: Bearer YOUR_API_KEY
```

### Response Format

All responses follow JSON:API specification v1.1 format.

**Success Response (Single Object):**

```json
{
  "jsonapi": {
    "version": "1.1"
  },
  "data": {
    "type": "petition",
    "id": "108820",
    "attributes": {
      "modules": {
        "id": "108820",
        "type": "petition",
        "slug": "2022-petition-application",
        "title": "2022 Petition Application",
        "case_number": "114-2022",
        "district": "Gurugram",
        "files": [
          {
            "url": "/uploads/cases/114-2022/document.pdf",
            "mime": "application/pdf",
            "name": "document.pdf",
            "transcription_ocr_en": "...",
            "transcription_ocr_hi": "..."
          }
        ],
        "created_on": "1767180487",
        "updated_on": "1767788697",
        "content_privacy": "public"
      },
      "slug": "2022-application"
    }
  }
}
```

**List Response:**

```json
{
  "jsonapi": {
    "version": "1.1"
  },
  "data": [
    {
      "type": "post",
      "id": "123",
      "attributes": {
        "modules": {
          "id": "123",
          "title": "Post 1",
          "body": "Content...",
          "slug": "post-1-abc123"
        },
        "slug": "post-1-abc123"
      }
    },
    {
      "type": "post",
      "id": "124",
      "attributes": {
        "modules": {
          "id": "124",
          "title": "Post 2",
          "body": "Content...",
          "slug": "post-2-def456"
        },
        "slug": "post-2-def456"
      }
    }
  ],
  "meta": {
    "total": 2
  }
}
```

**Types Configuration Response (webapp/0):**

```json
{
  "jsonapi": {
    "version": "1.1"
  },
  "data": {
    "type": "webapp",
    "id": "0",
    "attributes": {
      "modules": {
        "post": {
          "name": "Post",
          "slug": "post",
          "plural": "Posts",
          "description": "Blog posts",
          "total_objects": 42,
          "primary_module": "title",
          "modules": [
            {
              "input_slug": "title",
              "input_type": "text",
              "input_primary": true,
              "input_unique": false,
              "list_field": true,
              "var_type": "string"
            }
          ]
        },
        "webapp": {
          "size_in_gb": "2.45",
          "total_objects": 1250
        }
      },
      "slug": "webapp"
    }
  }
}
```

**Error Response:**

```json
{
  "errors": [
    {
      "status": "403",
      "title": "Forbidden",
      "detail": "Access denied"
    }
  ]
}
```

### Linked Modules

The API automatically resolves relationships defined in content types:

```php
// If 'post' type has linked_type for 'author_id' → 'user'
// Response includes linked object

{
  "data": {
    "type": "post",
    "id": "123",
    "attributes": {
      "modules": {
        "title": "My Post",
        "author_id": 5,
        "author_id__linked": {
          "id": 5,
          "type": "user",
          "name": "John Doe",
          "email": "john@example.com"
        }
      }
    }
  }
}
```

### Search Endpoint

The API provides a dedicated search endpoint for full-text search:

```bash
GET /api.php/search?q=query&type=post&page=1&per_page=20
```

**Query Parameters:**

- `q` - Search query (required)
- `type` - Content type to search (required)
- `page` - Page number (default: 1)
- `per_page` - Results per page (default: 10)
- `sort_by` - Sort field (e.g., `created_on:desc`)
- `facet_by` - Faceting field for aggregations
- Additional filters can be passed as query parameters

**Search Response:**

```json
{
  "jsonapi": { "version": "1.1" },
  "data": [
    {
      "type": "post",
      "id": "123",
      "attributes": {
        "modules": { "title": "Matching Post", ... },
        "search_highlights": {
          "title": "Matching <mark>Post</mark>"
        }
      }
    }
  ],
  "meta": {
    "search": {
      "total_found": 42,
      "search_time_ms": 15,
      "search_source": "database",
      "query": "tutorial",
      "page": 1,
      "per_page": 20,
      "facet_counts": {}
    }
  }
}
```

**JavaScript Example:**

```javascript
// Search for posts
const searchResults = await fetch(
  "https://tribe.yourwebsitelink.com/api.php/search?" +
    new URLSearchParams({
      q: "application",
      type: "petition",
      page: 1,
      per_page: 20,
      sort_by: "created_on:desc",
    }),
).then((r) => r.json());

console.log(searchResults.meta.search.total_found);
console.log(searchResults.data); // Array of matching records
```

### Advanced Filtering

**Multiple Filters (AND):**

```
?filter[status]=published&filter[category]=tech&filter[featured]=true
```

**Array Filters (OR):**

```
?filter[category][]=tech&filter[category][]=science
```

**Range Queries:**

```
?range[created_on][from]=1640000000&range[created_on][to]=1650000000
```

**Partial Search:**

```
?filter[title]=tutorial&show_partial_search_results=true
```

### CORS Configuration

The API automatically handles CORS:

```php
// Automatic headers
Access-Control-Allow-Origin: *
Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS
Access-Control-Allow-Headers: Content-Type, Authorization
Access-Control-Allow-Credentials: true
```

## Uploads Class - File Management

### Basic File Upload

```php
// uploads.php
require __DIR__ . '/_init.php';
$uploads = new \Tribe\Uploads;
$api = new \Tribe\API;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_POST = $api->requestBody;
}

if (($_FILES ?? false) || ($_POST ?? false)) {
    header('Content-type: application/json; charset=utf-8');
    echo json_encode($uploads->handleUpload(
        $_FILES ?? [],
        $_POST ?? [],
        $_GET ?? []
    ));
}
```

### Upload via URL

```javascript
// JavaScript/Ember example
async uploadFile(file) {
  try {
    const response = await file.upload('/uploads.php');
    const data = await response.json();

    if (data.status === 'success') {
      console.log(data.file.url);      // Main file URL
      console.log(data.file.name);     // File name
      console.log(data.file.mime);     // MIME type

      // Image variants (if image)
      console.log(data.file.xl.url);   // 2100px max
      console.log(data.file.lg.url);   // 1400px max
      console.log(data.file.md.url);   // 700px max
      console.log(data.file.sm.url);   // 350px max
      console.log(data.file.xs.url);   // 100px max

      // Video HLS (if video)
      console.log(data.file.hls.url);  // Adaptive streaming
    }
  } catch (error) {
    console.error(error);
  }
}
```

### Upload Response

```json
{
  "status": "success",
  "success": 1,
  "error": 0,
  "file": {
    "name": "document_abc123",
    "url": "/uploads/2025/01-January/26-Monday/document_abc123.pdf",
    "mime": "application/pdf"
  }
}
```

**Image Upload Response:**

```json
{
  "status": "success",
  "file": {
    "name": "photo_xyz789",
    "url": "/uploads/2025/01-January/26-Monday/photo_xyz789.jpg",
    "mime": "image/jpeg",
    "xl": {
      "name": "photo_xyz789",
      "url": "/uploads/2025/01-January/26-Monday/xl/photo_xyz789.jpg"
    },
    "lg": {
      "name": "photo_xyz789",
      "url": "/uploads/2025/01-January/26-Monday/lg/photo_xyz789.jpg"
    },
    "md": { "url": "..." },
    "sm": { "url": "..." },
    "xs": { "url": "..." }
  }
}
```

**Video Upload Response:**

```json
{
  "status": "success",
  "file": {
    "name": "video_abc123",
    "url": "/uploads/2025/01-January/26-Monday/video_abc123.mp4",
    "mime": "video/mp4",
    "hls": {
      "url": "/uploads/2025/01-January/26-Monday/hls/video_abc123.m3u8",
      "filename": "video_abc123.m3u8",
      "type": "adaptive",
      "qualities": ["xl", "lg", "md", "sm", "xs"]
    }
  }
}
```

### File Organization

```
/uploads/
  /YYYY/                    # Year
    /MM-Month/              # Month
      /DD-Day/              # Day
        file.pdf            # Original files
        /xs/                # 100x100px max
        /sm/                # 350x350px max
        /md/                # 700x700px max
        /lg/                # 1400x1400px max
        /xl/                # 2100x2100px max
        /hls/               # Video streaming files
          video.m3u8        # Master playlist
          video_xl.m3u8     # 2160p (4K)
          video_lg.m3u8     # 1080p (Full HD)
          video_md.m3u8     # 720p (HD)
          video_sm.m3u8     # 540p
          video_xs.m3u8     # 360p
          *.ts              # Video segments
```

### Supported File Types

**Images:**

- JPG, JPEG, PNG, WebP, GIF, SVG
- Auto-generates 5 sizes (xl, lg, md, sm, xs)
- Maintains aspect ratio

**Videos:**

- MP4, MOV, AVI, MKV, WebM
- Converts to HLS with multiple quality levels
- Generates adaptive streaming playlists

**Documents:**

- PDF, DOC, DOCX, TXT, HTML, JSON

**Audio:**

- MP3, M4A, OGG, OGA, WAV

**Subtitles:**

- VTT, SRT

### Image Processing

```php
$uploads = new \Tribe\Uploads();

// Get specific image size
$file = $uploads->getUploadedImageInSize(
    '/uploads/2025/01-January/26-Monday/photo.jpg',
    'md'  // Size: xs, sm, md, lg, xl
);

echo $file['url'];   // /uploads/.../md/photo.jpg
echo $file['path'];  // Filesystem path

// Get all versions of a file
$versions = $uploads->getUploadedFileVersions(
    '/uploads/2025/01-January/26-Monday/photo.jpg',
    'sm'  // Preferred thumbnail size
);

echo $versions['url']['source'];     // Original
echo $versions['url']['xl'];         // 2100px
echo $versions['url']['lg'];         // 1400px
echo $versions['url']['md'];         // 700px
echo $versions['url']['sm'];         // 350px
echo $versions['url']['xs'];         // 100px
echo $versions['url']['thumbnail'];  // Preferred size (sm)

// For videos
echo $versions['url']['hls'];        // Master playlist
echo $versions['url']['hls_xl'];     // 4K quality
echo $versions['url']['hls_lg'];     // 1080p quality
```

### Video HLS Streaming

**Quality Settings:**

```php
// Automatically generated qualities:

// XL - 2160p (4K)
// Resolution: 3840x2160
// Video bitrate: 15000k
// Audio bitrate: 192k

// LG - 1080p (Full HD)
// Resolution: 1920x1080
// Video bitrate: 8000k
// Audio bitrate: 128k

// MD - 720p (HD)
// Resolution: 1280x720
// Video bitrate: 4000k
// Audio bitrate: 128k

// SM - 540p
// Resolution: 960x540
// Video bitrate: 2000k
// Audio bitrate: 96k

// XS - 360p
// Resolution: 640x360
// Video bitrate: 1000k
// Audio bitrate: 64k
```

**Check HLS Status:**

```php
$uploads = new \Tribe\Uploads();

// Check if HLS conversion is complete
$isReady = $uploads->isHLSReady(
    '/uploads/2025/01-January/26-Monday/hls/video.m3u8'
);

// Get conversion status
$status = $uploads->getHLSStatus('video_abc123');

// Returns:
// [
//   'status' => 'completed',      // or 'processing', 'failed'
//   'progress' => 100,             // 0-100
//   'qualities' => [...]           // Available quality levels
// ]
```

### File Search

```php
// Search by filename and/or content
$results = $uploads->handleFileSearch(
    'invoice##2024',  // Search terms separated by ##
    true              // Deep search (includes PDF content)
);

echo json_encode([
    'by_file_name' => $results['by_file_name'],
    'by_file_content' => $results['by_file_content']
]);
```

### Copy File from URL

```php
$uploads = new \Tribe\Uploads();

$localUrl = $uploads->copyFileFromURL(
    'https://example.com/image.jpg'
);

// Returns: /uploads/2025/01-January/26-Monday/1640000000-image.jpg
```

### Delete File Record

```php
$uploads = new \Tribe\Uploads();

// Delete file and all its variants
$uploads->deleteFileRecord([
    'url' => '/uploads/2025/01-January/26-Monday/photo.jpg',
    'file' => [
        'xl' => ['url' => '...'],
        'lg' => ['url' => '...'],
        'md' => ['url' => '...'],
        'sm' => ['url' => '...'],
        'xs' => ['url' => '...'],
        'hls' => ['url' => '...']
    ]
]);
```

### Custom Upload Handling

```bash
# POST request with file
POST /uploads.php
Content-Type: multipart/form-data

# POST request with URL
POST /uploads.php
Content-Type: application/json
{
  "url": "https://example.com/existing-file.jpg"
}

# File search
POST /uploads.php
Content-Type: application/json
{
  "search": true,
  "q": "invoice##2024",
  "deep_search": true
}
```

## MySQL Class - Database Layer

### Basic Usage

```php
$sql = new \Tribe\MySQL();

// Execute query
$results = $sql->executeSQL("SELECT * FROM `data` WHERE `type`='post' LIMIT 10");

// Single result
if (count($results) === 1) {
    echo $results[0]['title'];
}

// Multiple results
foreach ($results as $row) {
    echo $row['title'];
}

// Get last insert ID
$id = $sql->lastInsertID();

// Close connection
$sql->closeConnection();
```

### Query Results

```php
// Returns array of results or 0 if no results
$results = $sql->executeSQL($query);

if ($results === false) {
    // Query error
    echo $sql->lastError;
    echo $sql->lastQuery;
} elseif ($results === 0) {
    // No results found
} else {
    // Process results
    foreach ($results as $row) {
        // ...
    }
}

// Properties
$sql->records;        // Number of rows returned
$sql->affected;       // Number of rows affected
$sql->lastQuery;      // Last executed query
$sql->lastError;      // Last error message
```

### Schema Awareness

```php
// The MySQL class is aware of the table schema:
$sql->schema = [
    'id',
    'content',
    'updated_on',
    'created_on',
    'user_id',
    'role_slug',
    'slug',
    'content_privacy',
    'type'
];

// Use schema to build dynamic queries safely
```

### Automatic Data Processing

```php
// Automatically:
// - Escapes values to prevent SQL injection
// - Strips slashes from retrieved data
// - Preserves JSON data integrity
// - Handles UTF-8 encoding (utf8mb4)
```

## Email Integration

### Sending OTP/Verification Emails

```php
// sendotp.php
require __DIR__ . '/../../_init.php';

use PHPMailer\PHPMailer\PHPMailer;

// Environment variables required:
// MAILPACE_SERVER_TOKEN
// MAILPACE_DOMAIN

// POST request
// {
//   "email": "user@example.com",
//   "otp": "123456"
// }

// Sends HTML email with OTP
// Subject: "Your OTP is 123456"
// From: no-reply@YOUR_DOMAIN
```

## Environment Configuration

### Required Variables

```env
# Database
DB_HOST=localhost
DB_PORT=3306
DB_NAME=your_database
DB_USER=your_username
DB_PASS=your_password

# Security
SSL=true
TRIBE_API_SECRET_KEY=your_secret_jwt_key
BARE_URL=yourdomain.com  # Trusted domain for internal requests

# File Storage (optional)
S3_UPLOADS_BUCKET_CDN_URL=https://cdn.example.com

# Email (optional)
MAILPACE_SERVER_TOKEN=your_mailpace_token
MAILPACE_DOMAIN=yourdomain.com

# Debugging (optional)
DISPLAY_ERRORS=false
```

### Development vs Production

```php
// Development
$_ENV['DISPLAY_ERRORS'] = true;

// Production
$_ENV['DISPLAY_ERRORS'] = false;
```

## Security Features

### Content Privacy Levels

- **public** - Accessible without authentication
- **private** - Requires API key, visible to authenticated users
- **draft** - Only visible to creator/owner
- **pending** - Awaiting moderation/approval
- **sent** - For sendable types (messages, notifications)

### API Key Security

```php
// Store API keys securely in database
// Keys should be:
// - Randomly generated (32+ characters)
// - Stored with access level (read/full)
// - Associated with specific domains (optional)
// - Rotatable/revocable
```

### SQL Injection Prevention

```php
// MySQL class automatically escapes values
$sql->executeSQL("SELECT * FROM `data` WHERE `id`='" . $safeId . "'");

// Always use the MySQL class methods
// Never concatenate raw user input into queries
```

### CORS Protection

```php
// API automatically validates:
// - Request origin against allowed domains
// - Preflight OPTIONS requests
// - Authentication headers
// - Content-Type headers
```

## Performance Optimization

### Database Indexing

```sql
-- Ensure indexes on frequently queried columns
CREATE INDEX idx_type ON data(type);
CREATE INDEX idx_slug ON data(slug);
CREATE INDEX idx_privacy ON data(content_privacy);
CREATE INDEX idx_user ON data(user_id);
CREATE INDEX idx_created ON data(created_on);
CREATE INDEX idx_updated ON data(updated_on);
```

### Query Optimization

```php
// Use specific field selection
$core->getIDs(['type' => 'post'], "0, 20");

// Rather than loading all then filtering
// Use backend filtering in getIDs()

// Leverage pagination
$core->getIDs(['type' => 'post'], "0, 20");  // First 20
$core->getIDs(['type' => 'post'], "20, 20"); // Next 20
```

### File Upload Optimization

```php
// Images auto-generate sizes asynchronously
// Videos convert to HLS in background
// Use appropriate size variants in frontend

// For thumbnails: xs (100px)
// For lists: sm (350px) or md (700px)
// For detail views: lg (1400px)
// For full-screen: xl (2100px)
```

### Caching Strategies

```php
// Static files served with cache headers
// API responses can be cached by client
// Use ETags for conditional requests

// Example: Cache-Control header
header('Cache-Control: public, max-age=3600');
```

## Usage Examples

### Complete CRUD Example

```php
require __DIR__ . '/_init.php';

$core = new \Tribe\Core();
$config = new \Tribe\Config();

// 1. Create a blog post
$postData = [
    'type' => 'post',
    'title' => 'Introduction to Tribe Framework',
    'body' => 'Tribe is a flexible PHP-based CMS...',
    'category' => 'tutorials',
    'author_id' => 5,
    'content_privacy' => 'public'
];
$postId = $core->pushObject($postData);
echo "Created post ID: $postId\n";

// 2. Read the post
$post = $core->getObject($postId);
echo "Post title: {$post['title']}\n";
echo "Created: " . date('Y-m-d H:i:s', $post['created_on']) . "\n";

// 3. Update the post
$updateData = [
    'id' => $postId,
    'type' => 'post',
    'title' => 'Getting Started with Tribe Framework'
];
$core->pushObject($updateData);
echo "Updated post title\n";

// 4. Search for posts
$ids = $core->getIDs(
    ['type' => 'post', 'category' => 'tutorials'],
    "0, 10",
    'created_on',
    'DESC',
    true
);
$posts = $core->getObjects($ids);
echo "Found " . count($posts) . " tutorial posts\n";

// 5. Delete the post
$core->deleteObject($postId);
echo "Deleted post ID: $postId\n";
```

### API Integration Example

```javascript
// JavaScript frontend example
class TribeAPI {
  constructor(baseURL, apiKey = null) {
    this.baseURL = baseURL;
    this.apiKey = apiKey;
  }

  async request(endpoint, options = {}) {
    const headers = {
      "Content-Type": "application/vnd.api+json",
      ...(this.apiKey && { Authorization: `Bearer ${this.apiKey}` }),
    };

    const response = await fetch(`${this.baseURL}${endpoint}`, {
      ...options,
      headers: { ...headers, ...options.headers },
    });

    return response.json();
  }

  // List posts
  async listPosts(filters = {}, page = { offset: 0, limit: 10 }) {
    const params = new URLSearchParams();

    Object.entries(filters).forEach(([key, value]) => {
      params.append(`filter[${key}]`, value);
    });

    params.append("page[offset]", page.offset);
    params.append("page[limit]", page.limit);

    return this.request(`/api.php/post?${params}`);
  }

  // Get single post by ID
  async getPost(id) {
    return this.request(`/api.php/post/${id}`);
  }

  // Get single post by slug
  async getPostBySlug(slug) {
    return this.request(`/api.php/post/${slug}`);
  }

  // Get types configuration
  async getTypes() {
    return this.request("/api.php/webapp/0");
  }

  // Create post
  async createPost(data) {
    return this.request("/api.php/post", {
      method: "POST",
      body: JSON.stringify({
        data: {
          type: "post",
          attributes: { modules: data },
        },
      }),
    });
  }

  // Update post
  async updatePost(id, data) {
    return this.request(`/api.php/post/${id}`, {
      method: "PATCH",
      body: JSON.stringify({
        data: {
          type: "post",
          id: String(id),
          attributes: { modules: data },
        },
      }),
    });
  }

  // Delete post
  async deletePost(id) {
    return this.request(`/api.php/post/${id}`, {
      method: "DELETE",
    });
  }

  // Upload file
  async uploadFile(file) {
    const formData = new FormData();
    formData.append("file", file);

    const response = await fetch(`${this.baseURL}/uploads.php`, {
      method: "POST",
      body: formData,
      headers: this.apiKey ? { Authorization: `Bearer ${this.apiKey}` } : {},
    });

    return response.json();
  }
}

// Usage
const api = new TribeAPI("https://tribe.yourwebsitelink.com", "your_api_key");

// Get types configuration
const config = await api.getTypes();
console.log(config.data.attributes.modules.post); // Post type definition

// Create post
const newPost = await api.createPost({
  title: "My First Post",
  body: "This is the content",
  content_privacy: "public",
});

// List posts
const posts = await api.listPosts(
  { category: "tech" },
  { offset: 0, limit: 20 },
);

// Get post by slug
const post = await api.getPostBySlug("my-first-post-abc123");

// Upload image
const fileInput = document.querySelector("#file-input");
const uploadResult = await api.uploadFile(fileInput.files[0]);
console.log(uploadResult.file.md.url); // Medium-sized image
```

### Multi-Language Content

```php
// Content type with multi-language support
$types = [
    'product' => [
        'modules' => [
            [
                'input_slug' => 'title',
                'input_lang' => [
                    ['slug' => 'en', 'title' => 'English'],
                    ['slug' => 'es', 'title' => 'Spanish'],
                    ['slug' => 'fr', 'title' => 'French']
                ]
            ]
        ]
    ]
];

// Saving multi-language content
$product = [
    'type' => 'product',
    'title_en' => 'Blue Shoes',
    'title_es' => 'Zapatos Azules',
    'title_fr' => 'Chaussures Bleues',
    'content_privacy' => 'public'
];
$productId = $core->pushObject($product);

// Retrieving
$product = $core->getObject($productId);
echo $product['title_en'];  // Blue Shoes
echo $product['title_es'];  // Zapatos Azules
```

### Linked Content Types

```php
// Define relationships
$types = [
    'post' => [
        'modules' => [
            [
                'input_slug' => 'author_id',
                'linked_type' => 'user'
            ],
            [
                'input_slug' => 'category_id',
                'linked_type' => 'category'
            ]
        ]
    ]
];

// Create linked content
$post = [
    'type' => 'post',
    'title' => 'My Post',
    'author_id' => 5,
    'category_id' => 3
];
$core->pushObject($post);

// API automatically includes linked objects
GET /api/v1.1/post/123

// Response includes:
{
  "data": {
    "attributes": {
      "author_id": 5,
      "author_id__linked": {
        "id": 5,
        "type": "user",
        "name": "John Doe"
      },
      "category_id": 3,
      "category_id__linked": {
        "id": 3,
        "type": "category",
        "name": "Technology"
      }
    }
  }
}
```

## Common Patterns

### Pagination

```php
// Page 1
$page1 = $core->getIDs(['type' => 'post'], "0, 20");

// Page 2
$page2 = $core->getIDs(['type' => 'post'], "20, 20");

// Page 3
$page3 = $core->getIDs(['type' => 'post'], "40, 20");

// Get total for pagination
$total = $core->getIDsTotalCount(['type' => 'post'], "0, 20");
$totalPages = ceil($total / 20);
```

### Filtering & Sorting

```php
// Complex filter
$results = $core->getIDs(
    search_arr: [
        'type' => 'product',
        'category' => ['electronics', 'computers'],
        'status' => 'available'
    ],
    limit: "0, 50",
    sort_field: ['price', 'created_on'],
    sort_order: ['ASC', 'DESC'],
    show_partial_search_results: true
);
```

### Unique Constraints

```php
// Ensure unique slugs
$post = [
    'type' => 'post',
    'title' => 'Unique Title',
    'content_privacy' => 'public'
];

$postId = $core->pushObject($post);

if ($postId === 0) {
    // Uniqueness constraint violated
    echo "A post with this title already exists\n";
} else {
    echo "Created post ID: $postId\n";
}
```

### Soft Updates

```php
// Update only specific fields (default behavior)
$update = [
    'id' => 123,
    'type' => 'post',
    'title' => 'New Title'
    // Other fields remain unchanged
];
$core->pushObject($update);

// Complete replacement
$replacement = [
    'id' => 123,
    'type' => 'post',
    'title' => 'New Title',
    'body' => 'New content'
    // All other fields will be removed
];
$core->pushObject($replacement, true);  // true = overwrite
```

## Troubleshooting

### Common Issues

**1. Database Connection Failed**

```
Error: Unable to connect to MySQL
```

- Check DB_HOST, DB_NAME, DB_USER, DB_PASS in .env
- Verify MySQL service is running
- Check firewall/network connectivity

**2. Uniqueness Constraint Violation**

```
pushObject() returns 0
```

- Another object with same primary field value exists
- Check if input_unique is set on primary module
- Generate new slug or modify unique field

**3. File Upload Fails**

```
Permission denied / mkdir failed
```

- Check write permissions on /uploads directory
- Set permissions: `chmod 755 uploads`
- Verify PHP upload_max_filesize and post_max_size

**4. HLS Video Conversion Stuck**

```
Video uploaded but HLS not available
```

- Check FFmpeg is installed and accessible
- View conversion log: `/tmp/ffmpeg_*.log`
- Increase server timeout settings

**5. API Returns Empty Results**

```
{"data": []}
```

- Check content_privacy settings
- Verify authentication for private content
- Use show_public_objects_only=false with auth

**6. Linked Modules Not Resolving**

```
author_id__linked not in response
```

- Verify linked_type is defined in type configuration
- Check that linked object exists
- Ensure proper API version (v1.1)

### Debug Mode

```php
// Enable error display
$_ENV['DISPLAY_ERRORS'] = true;

// View SQL queries
$debug = true;
$core->getIDs(
    ['type' => 'post'],
    debug_show_sql_statement: true
);

// Check last query
$sql = new \Tribe\MySQL();
$sql->executeSQL($query);
echo $sql->lastQuery;
echo $sql->lastError;
```

### Performance Issues

```php
// Optimize queries
// BAD: Load all, filter in PHP
$all = $core->getObjects($core->getIDs(['type' => 'post'], "0, 1000"));
$filtered = array_filter($all, fn($p) => $p['status'] === 'published');

// GOOD: Filter in database
$filtered = $core->getObjects($core->getIDs([
    'type' => 'post',
    'status' => 'published'
], "0, 1000"));

// Use pagination
$page = $core->getIDs(['type' => 'post'], "0, 20");

// Optimize image loading
// Use appropriate size variant
<img src="{{ file.sm.url }}" />  <!-- 350px for thumbnails -->
<img src="{{ file.lg.url }}" />  <!-- 1400px for detail view -->
```

## Best Practices

### Content Type Design

```php
// Define clear, focused content types
// BAD: Generic "content" type with 50 modules
// GOOD: Specific types (post, page, product, user)

// Use appropriate field types
[
    'input_slug' => 'price',
    'input_type' => 'number',
    'var_type' => 'float'
]

// Mark primary/unique fields
[
    'input_slug' => 'email',
    'input_primary' => true,
    'input_unique' => true
]
```

### API Usage

```javascript
// Use proper HTTP methods
GET    - Reading data
POST   - Creating new objects
PATCH  - Updating existing objects
DELETE - Removing objects

// Include proper headers
headers: {
  'Content-Type': 'application/vnd.api+json',
  'Authorization': 'Bearer YOUR_KEY'
}

// Handle errors gracefully
try {
  const data = await api.getPost(123);
} catch (error) {
  if (error.status === 404) {
    // Handle not found
  } else if (error.status === 403) {
    // Handle unauthorized
  }
}
```

### File Management

```php
// Store file references, not files in database
$post = [
    'type' => 'post',
    'image_url' => '/uploads/2025/01-January/26-Monday/photo.jpg',
    'files' => [
        ['url' => '/uploads/.../document.pdf', 'name' => 'Report'],
        ['url' => '/uploads/.../image.jpg', 'name' => 'Photo']
    ]
];

// Use appropriate image sizes
// Thumbnails: xs or sm
// List views: sm or md
// Detail views: lg
// Full-screen: xl
// Original: Only when necessary

// Clean up old files periodically
// Implement file retention policies
// Use S3 for long-term storage
```

### Security

```php
// Always validate input
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    throw new Exception('Invalid email');
}

// Use content_privacy appropriately
'draft'   - Work in progress
'pending' - Needs approval
'private' - Authenticated only
'public'  - Everyone

// Rotate API keys regularly
// Use read-only keys when possible
// Implement rate limiting
// Log security events
```

### Performance

```php
// Batch operations
$core->deleteObjects([1, 2, 3, 4, 5]);  // Better than 5 individual deletes

// Use pagination
$limit = "0, 20";  // Not "0, 1000"

// Selective loading
// Only load what you need
$ids = $core->getIDs(['type' => 'post', 'status' => 'published']);

// Cache frequently accessed data
// Use ETags for conditional requests
// Implement HTTP caching headers
```

## Advanced Topics

### Custom Endpoints

```php
// Create custom endpoint: api/custom.php
require __DIR__ . '/_init.php';
$api = new \Tribe\API;
$core = new \Tribe\Core();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Custom logic
    $stats = [
        'total_posts' => $core->getIDsTotalCount(['type' => 'post']),
        'total_users' => $core->getIDsTotalCount(['type' => 'user']),
        'recent_posts' => $core->getObjects(
            $core->getIDs(['type' => 'post'], "0, 5", 'created_on', 'DESC')
        )
    ];

    echo json_encode(['data' => $stats]);
}
```

### Webhooks Integration

```php
// After object creation/update
$postId = $core->pushObject($post);

// Trigger webhook
if ($postId && ($_ENV['WEBHOOK_URL'] ?? false)) {
    $payload = json_encode([
        'event' => 'post.created',
        'object_id' => $postId,
        'object_type' => 'post',
        'timestamp' => time()
    ]);

    $ch = curl_init($_ENV['WEBHOOK_URL']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    curl_close($ch);
}
```

### Custom Authentication

```php
// Implement custom auth logic
class CustomAuth {
    public static function validateToken($token) {
        // Verify JWT or custom token
        // Return user ID or false
    }

    public static function checkPermission($userId, $action, $objectType) {
        // Check if user can perform action
        // Return true or false
    }
}

// Use in API
if (!CustomAuth::validateToken($_SERVER['HTTP_AUTHORIZATION'] ?? '')) {
    http_response_code(401);
    echo json_encode(['errors' => [['status' => '401', 'title' => 'Unauthorized']]]);
    exit;
}
```

## Migration & Deployment

### Database Migration

```sql
-- Backup existing data
mysqldump -u username -p database_name > backup.sql

-- Migrate to Tribe structure
CREATE TABLE data_new LIKE data;

INSERT INTO data_new (id, content, type, slug, content_privacy, user_id, created_on, updated_on)
SELECT
  id,
  JSON_OBJECT('title', title, 'body', body, 'status', status) as content,
  'post' as type,
  slug,
  CASE
    WHEN status = 'published' THEN 'public'
    WHEN status = 'draft' THEN 'draft'
    ELSE 'private'
  END as content_privacy,
  author_id as user_id,
  UNIX_TIMESTAMP(created_at) as created_on,
  UNIX_TIMESTAMP(updated_at) as updated_on
FROM old_posts_table;

-- Verify and swap tables
RENAME TABLE data TO data_old, data_new TO data;
```

### Deployment Checklist

```bash
# 1. Set environment variables
cp .env.example .env
# Edit .env with production values

# 2. Set permissions
chmod 755 uploads/
chmod 644 .env

# 3. Install dependencies
composer install --no-dev --optimize-autoloader

# 4. Configure web server
# - Point document root to public/
# - Enable mod_rewrite (Apache)
# - Configure PHP-FPM (Nginx)

# 5. Set up SSL
# - Install SSL certificate
# - Force HTTPS redirects
# - Set SSL=true in .env

# 6. Database optimization
# - Add indexes
# - Configure connection pooling
# - Set up read replicas (if needed)

# 7. File storage
# - Configure S3_UPLOADS_BUCKET_CDN_URL
# - Set up CDN for static assets
# - Enable gzip compression

# 8. Monitoring
# - Enable error logging
# - Set up performance monitoring
# - Configure backup schedule

# 9. Security hardening
# - Disable directory listing
# - Set restrictive file permissions
# - Implement rate limiting
# - Configure firewall rules
```

### Backup Strategy

```bash
# Database backup (daily)
mysqldump -u user -p database | gzip > backup-$(date +%Y%m%d).sql.gz

# File backup (to S3)
aws s3 sync /path/to/uploads/ s3://bucket-name/uploads/ --delete

# Automated backup script
#!/bin/bash
DATE=$(date +%Y%m%d)
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME | gzip > /backups/db-$DATE.sql.gz
aws s3 sync /var/www/uploads/ s3://tribe-backups/uploads-$DATE/
find /backups -mtime +30 -delete  # Remove backups older than 30 days
```

## License

This project is licensed under the [GNU GPL v3 License](LICENSE.md).

## Support

- Documentation: https://github.com/tribe-framework/tribe
- Issues: https://github.com/tribe-framework/tribe-core/issues
- Community: https://junction.express
