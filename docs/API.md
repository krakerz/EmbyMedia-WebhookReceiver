# API Documentation

This document provides detailed information about the EmbyMedia-WebhookReceiver API endpoints.

## Base URL

All API endpoints are relative to your application's base URL.

## Authentication

The webhook endpoint supports optional authentication via a secret parameter:

```
POST /emby/webhook?secret=your_webhook_secret
```

Configure the secret in your `.env` file:
```env
WEBHOOK_SECRET=your_secure_secret_here
```

## Endpoints

### Webhook Endpoint

**POST** `/emby/webhook`

Receives webhook notifications from Emby media server.

#### Headers
```
Content-Type: application/json
```

#### Query Parameters
- `secret` (optional): Webhook secret for authentication if `WEBHOOK_SECRET` is configured

#### Request Body

Accepts standard Emby webhook payload format. The application processes various event types:

**Library Events:**
- `library.new` - New media added to library
- `item.added` - Item added to library

**Playback Events:**
- `playback.start` - User started playback
- `playback.stop` - User stopped playback
- `playback.pause` - User paused playback

**User Events:**
- `user.created` - New user created
- `user.deleted` - User deleted

#### Example Request Body

```json
{
  "Event": "library.new",
  "Item": {
    "Name": "The Matrix",
    "Type": "Movie",
    "Path": "/media/movies/The Matrix (1999)/The Matrix (1999).mkv",
    "ProductionYear": 1999,
    "Overview": "A computer programmer discovers reality is a simulation.",
    "Genres": ["Action", "Sci-Fi"],
    "CommunityRating": 8.7,
    "OfficialRating": "R",
    "RunTimeTicks": 81360000000,
    "DateCreated": "2025-07-28T12:00:00.0000000Z",
    "PremiereDate": "1999-03-31T00:00:00.0000000Z",
    "ProviderIds": {
      "IMDB": "tt0133093",
      "Tvdb": "290434"
    },
    "ExternalUrls": [
      {
        "Name": "IMDb",
        "Url": "https://www.imdb.com/title/tt0133093"
      }
    ],
    "Width": 1920,
    "Height": 1080,
    "Container": "mkv",
    "Size": 2147483648
  },
  "User": {
    "Name": "john_doe",
    "Id": "abc123def456"
  },
  "Server": {
    "Name": "Home Media Server",
    "Id": "server123",
    "Version": "4.7.0.0"
  }
}
```

#### Response

**Success (200)**
```json
{
  "status": "success"
}
```

**Error (401)** - Invalid webhook secret
```json
{
  "status": "error",
  "message": "Unauthorized"
}
```

**Error (500)** - Processing error
```json
{
  "status": "error",
  "message": "Error message details"
}
```

### Dashboard Endpoints

#### Get Dashboard

**GET** `/`

Displays the webhook dashboard with pagination and filtering.

**Query Parameters:**
- `page` (optional): Page number for pagination (default: 1)
- `filter` (optional): Filter by item type (Movie, Episode, Audio, etc.)

**Example:**
```
GET /?page=2&filter=Movie
```

#### Get Webhook Details

**GET** `/webhook/{uuid}`

Displays detailed information for a specific webhook event.

**Parameters:**
- `uuid`: The UUID of the webhook record

**Example:**
```
GET /webhook/550e8400-e29b-41d4-a716-446655440000
```

## Data Processing

### Metadata Extraction

The application extracts and stores the following metadata from webhook payloads:

- **Basic Info**: Name, type, path, overview
- **Media Details**: Year, premiere date, runtime, genres, ratings
- **Technical Info**: Container, size, dimensions (width/height)
- **Provider IDs**: IMDB, TVDB, TMDB identifiers
- **External URLs**: Links to provider pages
- **Series Info**: For TV content - series name, season/episode numbers

### Image Fetching

Cover images are fetched in priority order:

1. **Emby Server** (primary source)
   - Uses item ID and image tags from webhook
   - Format: `{EMBY_BASE_URL}/emby/Items/{item_id}/Images/Primary?tag={image_tag}&quality=90`

2. **TVDB** (fallback for TV content)
   - Uses TVDB ID from provider IDs
   - Fetches series or episode artwork

3. **TMDB** (fallback using IMDB ID)
   - Uses IMDB ID from provider IDs
   - Supports both movies and TV shows

4. **TMDB Search** (final fallback)
   - Searches by title and year
   - Used when no provider IDs are available

### Database Schema

Webhook data is stored with the following structure:

```sql
CREATE TABLE emby_webhooks (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    uuid VARCHAR(36) UNIQUE NOT NULL,
    event_type VARCHAR(255),
    item_type VARCHAR(255),
    item_name VARCHAR(255),
    item_path TEXT,
    user_name VARCHAR(255),
    server_name VARCHAR(255),
    metadata JSON,
    raw_payload JSON,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

## Configuration

### Environment Variables

Key configuration options:

```env
# Webhook settings
WEBHOOK_SECRET=your_secret_here
WEBHOOK_REFRESH_TIMER=30
WEBHOOKS_PAGINATION_PER_PAGE=12
WEBHOOK_ALLOWED_ITEM_TYPES="Movie,Episode,Audio"

# API keys
TVDB_API_KEY=your_tvdb_key
IMDB_API_KEY=your_tmdb_key
EMBY_API_KEY=your_emby_key

# Display options
SHOW_RAW_WEBHOOK_DATA=true
SHOW_FILE_LOCATION=true
SHOW_WEBHOOK_EVENT_DETAILS=true
SHOW_PROVIDER_IDS=true
SHOW_PREMIERE_DATE=true

# Timing
NEW_CARD_MINUTES=60
APP_TIMEZONE=UTC
```

## Error Handling

The application includes comprehensive error handling:

- **Authentication Errors**: Invalid webhook secrets return 401
- **Processing Errors**: Exceptions during webhook processing return 500
- **Validation**: Malformed payloads are logged but don't cause failures
- **Image Fetching**: Failed image requests are logged but don't stop processing
- **Database**: Connection issues are handled gracefully

## Logging

All webhook activity is logged for debugging:

```php
// Successful webhook processing
Log::info('Emby webhook received', ['payload' => $payload]);

// Image fetching success
Log::info('Cover image fetched successfully', [
    'item_name' => $itemName,
    'source' => $source
]);

// Authentication failures
Log::warning('Unauthorized Emby webhook access attempt', [
    'provided_secret' => $secret,
    'ip_address' => $ip
]);

// Processing errors
Log::error('Error processing Emby webhook', [
    'error' => $exception->getMessage(),
    'payload' => $payload
]);
```

## Rate Limiting

For production deployments, consider implementing rate limiting:

```nginx
# In nginx.conf http block
limit_req_zone $binary_remote_addr zone=webhook:10m rate=30r/m;

# In server block
location /emby/webhook {
    limit_req zone=webhook burst=10 nodelay;
    # ... other config
}
```

## Testing

Use the provided test endpoints to verify functionality:

```bash
# Test webhook endpoint
curl -X POST http://localhost:8000/emby/webhook \
  -H "Content-Type: application/json" \
  -d '{"Event": "library.new", "Item": {"Name": "Test", "Type": "Movie"}}'

# Test dashboard
curl http://localhost:8000/

# Test with filtering
curl http://localhost:8000/?filter=Movie&page=1
```