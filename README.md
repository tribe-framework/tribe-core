# Tribe Core Documentation

## Overview

Tribe Core is a PHP-based headless CMS framework that provides a JSON API for content management. It features dynamic content types, authentication, file uploads, and comprehensive API access controls. The framework is designed to be flexible and can handle various content types with customizable modules and relationships.

## Core Architecture

### Database Schema
The framework uses a simplified JSON-based database schema with a single `data` table containing:
- `id` - Primary key
- `content` - JSON field storing all object data
- `type` - Content type identifier
- `slug` - URL-friendly identifier
- `content_privacy` - Privacy level (public, private, draft, pending)
- `user_id` - Owner identifier
- `created_on` / `updated_on` - Timestamps

## Core Classes

### 1. API Class (`API.php`)

The main API handler that processes HTTP requests and returns JSON API responses.

#### Key Features:
- **CORS Handling**: Automatic CORS headers for cross-origin requests
- **API Key Authentication**: Multiple levels of access control
- **JSON API Compliance**: Follows JSON API specification
- **Linked Modules**: Automatic relationship resolution
- **Domain Whitelisting**: Security through domain restrictions

#### HTTP Methods Supported:
- `GET` - Retrieve objects or collections
- `POST` - Create new objects
- `PATCH` - Update existing objects
- `DELETE` - Remove objects

#### Authentication Levels:
1. **Junction Domains**: Full access for internal domains
2. **API Keys with Full Access**: Read/write operations
3. **API Keys with Read Access**: Read-only operations
4. **Public Access**: Limited to public content only

#### Special Features:
- **Linked Modules Processing**: Automatically resolves relationships between content types
- **Pagination**: Built-in pagination with offset/limit support
- **Filtering**: Advanced filtering with multiple operators
- **Sorting**: Flexible sorting including random ordering

### 2. Core Class (`Core.php`)

Central logic handler for object management and database operations.

#### Primary Methods:

##### Object Management:
- `pushObject(array $post)` - Create or update objects
- `getObject($identifier)` - Retrieve single object
- `getObjects($identifier)` - Retrieve multiple objects
- `deleteObject(int $id)` - Delete single object
- `deleteObjects(array $ids)` - Bulk delete objects

##### Search and Filtering:
- `getIDs($search_array, $limit, $sort_field, $sort_order, ...)` - Advanced search with multiple parameters
- `getIDsTotalCount(...)` - Get total count for pagination

##### Utility Functions:
- `slugify($string)` - Generate URL-friendly slugs
- `getAttribute($id, $key)` - Get specific attribute
- `pushAttribute($id, $key, $value)` - Update specific attribute

#### Advanced Features:
- **Soft Delete**: Optional soft deletion of records
- **Unique Constraints**: Automatic uniqueness validation
- **Type Validation**: Variable type enforcement
- **Partial Search**: Flexible text searching capabilities

### 3. Config Class (`Config.php`)

Manages configuration and content type definitions.

#### Configuration Sources:
1. **Dynamic Types**: Latest uploaded types configuration
2. **Local Config**: Static configuration files
3. **Remote Blueprints**: GitHub-hosted default configurations

#### Type Management:
- **Module Definitions**: Field definitions for content types
- **Linked Modules**: Relationship configurations
- **Primary Modules**: Main identifier fields
- **Privacy Controls**: Content visibility settings

#### Auto-Generated Fields:
- Automatically adds `content_privacy` field to all types
- Configures appropriate privacy options based on type capabilities

### 4. MySQL Class (`MySQL.php`)

Database abstraction layer providing secure MySQL operations.

#### Features:
- **Prepared Statements**: SQL injection protection
- **UTF8MB4 Support**: Full Unicode support
- **Connection Management**: Automatic connection handling
- **Error Handling**: Comprehensive error reporting
- **Result Processing**: Automatic result formatting

#### Schema Awareness:
Maintains knowledge of table schema for optimized queries and proper field handling.

### 5. Uploads Class (`Uploads.php`)

Comprehensive file management system with automatic image processing.

#### Upload Features:
- **Multiple File Types**: Images, videos, documents, audio
- **Image Variants**: Automatic generation of multiple sizes (xs, sm, md, lg, xl)
- **Video Processing**: Cloudflare Stream integration
- **File Search**: Content-based file searching
- **URL Copying**: Remote file importing

#### Image Processing:
Automatically generates 5 different sizes for uploaded images:
- **XL**: 2100x2100px maximum
- **LG**: 1400x1400px maximum  
- **MD**: 700x700px maximum
- **SM**: 350x350px maximum
- **XS**: 100x100px maximum

#### Security Features:
- **MIME Type Validation**: Restricts allowed file types
- **Organized Storage**: Date-based directory structure
- **Secure Filenames**: Prevents directory traversal attacks

### 6. Backup Class (`Backup.php`)

Automated backup system for database and file storage.

#### Backup Types:
- **MySQL Database**: Compressed SQL dumps with password protection
- **File Uploads**: S3 synchronization for redundancy
- **Retention Policies**: Intelligent backup cleanup

#### Integration:
- **S3 Compatible**: Works with any S3-compatible storage
- **Compression**: 7zip compression for database backups
- **Background Processing**: Non-blocking backup operations

## API Usage Examples

### Basic Object Retrieval
```http
GET /api/v1.1/post
```
Returns paginated list of posts.

### Single Object
```http
GET /api/v1.1/post/123
```
Returns specific post with ID 123.

### Filtering
```http
GET /api/v1.1/post?filter[title]=example&filter[status]=published
```
Returns posts matching filter criteria.

### Pagination
```http
GET /api/v1.1/post?page[offset]=20&page[limit]=10
```
Returns 10 posts starting from offset 20.

### Sorting
```http
GET /api/v1.1/post?sort=-created_on,title
```
Sorts by creation date (descending) then title (ascending).

### Creating Objects
```http
POST /api/v1.1/post
Content-Type: application/vnd.api+json

{
  "data": {
    "type": "post",
    "attributes": {
      "modules": {
        "title": "My Post",
        "content": "Post content here"
      }
    }
  }
}
```

## Security Features

### API Key Management
- **Read-Only Keys**: Limited to GET operations
- **Full Access Keys**: Complete CRUD operations
- **Domain Whitelisting**: Restrict keys to specific domains
- **Development Mode**: Special localhost access for development

### Content Privacy Levels
- **Public**: Accessible without authentication
- **Private**: Requires valid API key
- **Draft**: Author-only access
- **Pending**: Awaiting moderation

### CORS Protection
- **Origin Validation**: Checks request origins
- **Credential Support**: Secure cookie handling
- **Preflight Handling**: Proper OPTIONS request handling

## Configuration

### Environment Variables
- `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` - Database connection
- `SSL` - Enable HTTPS-only cookies
- `TRIBE_API_SECRET_KEY` - JWT signing secret
- `S3_*` - S3 backup configuration
- `CLOUDFLARE_STREAM_*` - Video processing integration

### Content Types
Content types are defined in JSON format and can be:
- Stored locally in `/config/types.json`
- Uploaded dynamically through the API
- Loaded from remote GitHub repositories

## File Organization

```
/uploads/
  /YYYY/
    /MM-Month/
      /DD-Day/
        /original-files
        /xs/ (100px thumbnails)
        /sm/ (350px thumbnails)
        /md/ (700px thumbnails)
        /lg/ (1400px thumbnails)
        /xl/ (2100px thumbnails)
```

## Error Handling

The framework provides comprehensive error handling:
- **HTTP Status Codes**: Proper REST status codes
- **JSON Error Responses**: Structured error messages
- **Development Logging**: Detailed error information in development
- **Graceful Degradation**: Fallback behaviors for missing data

## Performance Features

### Optimization Strategies:
- **Bulk Operations**: Efficient batch processing for related objects
- **Query Optimization**: Smart JOIN elimination and selective loading
- **Caching Headers**: Proper HTTP caching directives
- **Lazy Loading**: On-demand relationship resolution

### Scalability:
- **Stateless Design**: Horizontal scaling capability
- **JSON Storage**: Flexible schema evolution
- **CDN Integration**: Asset delivery optimization
- **Background Processing**: Non-blocking operations