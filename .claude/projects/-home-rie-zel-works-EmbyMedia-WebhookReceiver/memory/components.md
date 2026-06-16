name: emby-webhook-core-components
description: Core files to read for understanding the application's main components and functionality
---

# Core Components & Files

## Primary Source Files (Must Know)

### Entry Points
| File | Purpose | When to Read |
|--|--|--|
| `routes/web.php` | Routes — only 3 routes, memorize them | Understanding flow, debugging endpoints |
| `.env.example` | All configurable settings with defaults | Adding features, tuning behavior |

---

### Webhook Ingestion Chain (Primary Business Logic)

**Request Entry:**
- `app/Http/Controllers/EmbyWebhookController.php::handleWebhook()` — single source of truth for POST handling
  - Validates `WEBHOOK_SECRET` query param first, returns 401 if wrong
  - Extracts structured data via private helper methods (keep them together)
  - Calls `ImageFetchingService` only for `library.new`/`item.added` events

**Data Extraction Helpers:**
- `extractEventType()` — checks `Event` or `NotificationType` fields
- `extractItemData()` — reads from `Item` object: type, name, path  
- `extractUserData()` — reads `User.Name`, `User.Id`
- `extractServerData()` — reads `Server.Name`, `Version`
- `extractMetadata()` — the big one; pulls overview, year, genres, provider_ids, external_urls, media dimensions

**Persistence:**
- `app/Models/EmbyWebhook.php` — Eloquent model with UUID route key, JSON casts, two accessors:
  - `isMediaAdded()` → true for library.new/item.added events only
  - `isRecentlyAdded()` → checks NEW_CARD_MINUTES window from creation

**Database Schema:**
- `database/migrations/2025_07_25_142056_add_uuid_to_emby_webhooks_table.php` — UUID added as route key
- Table columns: event_type, item_type/name/path, user_name, server_name, metadata (JSON), raw_payload (JSON)

---

### Cover Image Resolution Service

**Orchestrator:** `app/Services/ImageFetchingService.php`
- Injects Tvdb, Imdb, Emby services at constructor
- `fetchCoverImage()` runs the 4-stage fallback chain:
  ```
  1. embyService->getCoverImageFromItem(rawItem)   // item ID + image tag
     ↓ (fail or episode without poster?)
  2. embyService->getSeriesImageFromItem(rawItem)
     ↓ (TV content with provider IDs?)
  3. tvdbService->getEpisodeArtwork(tvdbId) OR getSeriesArtwork(tvdbId)
     ↓ (IMDB ID available?)
  4. imdbService->getMoviePoster(imdbId) / getTvShowPoster(imdbId)
     ↓ (no IDs or title search needed?)
  5. imdbService->searchByTitle(itemName, year, 'movie'|'tv')
  ```

**Fallback for Episodes:** `fetchSeriesPosterForEpisode(metadata)` — tries same chain on series-level data when episode poster fails.

---

### External API Services (Keep Them Together)

| Service | File | Purpose |
|--|--|--|
| **EmbyService** | `app/Services/EmbyService.php` | Direct asset fetch from Emby server using item IDs and image tags |
| **TvdbService** | `app/Services/TvdbService.php` | Login to TVDB v4, request artwork by series ID or episode ID |
| **ImdbService** | `app/Services/ImdbService.php` | TMDB v3 API calls: find (by IMDB ID), movie poster, TV show poster, search-by-title |

Each service has a single responsibility and is swapped via constructor injection — no magic.

---

### Database Seeding & Testing

**Sample Data:**
- `database/seeders/EmbyWebhookSeeder.php` — 4 sample webhooks (Matrix, Breaking Bad pilot, Inception playback.start, Pink Floyd album)
- Use in dev: `php artisan db:seed --class=EmbyWebhookSeeder` to populate DB with demo data

**Feature Tests:**
- `tests/Feature/ImageFetchingTest.php` — mocks Http::fake on TVDB/TMDB endpoints; asserts poster_url appears in metadata with correct source
- `tests/Feature/EmbyImageFetchingTest.php` — likely tests full webhook flow through controller → service chain
- All tests use RefreshDatabase trait (fresh DB per test)

---

### Frontend Files

**Layout & Styles:**
- `resources/views/layouts/app.blade.php` — single layout with gradient background, glass-effect navbar, footer, auto-refresh countdown script at bottom
- CSS is in Blade (`<style>` blocks), no separate app.css beyond Vite entry

**Views (only two):**
| File | Purpose | Key Features |
|--|--|--|
| `resources/views/webhooks/index.blade.php` | Dashboard grid | Filter buttons, toggle controls for images/descriptions, pagination with "flowbite" style, cookie-based toggles at bottom via inline `<script>` |
| `resources/views/webhooks/show.blade.php` | Individual item details | Click handler expands metadata (overview, genres, dimensions, external URLs) |

**Utilities:**
- `app/Http/Middleware/Authenticate.php` — redirects unauthenticated visitors to index
- `config/services.php` — central place for API keys and webhook UI toggles (refresh_timer, show_file_location, allowed_item_types, pagination_per_page)

---

## File Organization Quick Map
```
app/
├── Http/Controllers/EmbyWebhookController.php   ← main entry point
├── Models/EmbyWebhook.php                       ← Eloquent model, UUID route key
└── Services/
    ├── EmbyService.php                          ← direct Emby server fetches
    ├── TvdbService.php                          ← TVDB API v4
    └── ImdbService.php                          ← TMDB API v3

database/migrations/*.php                        ← only 2 migrations (ids + uuid)
database/seeders/EmbyWebhookSeeder.php            ← demo data generator
resources/views/webhooks/index.blade.php          ← grid view, filters, pagination
routes/web.php                                    ← 3 routes total
```

## When to Read Which File
- **Adding a new event type** → `EmbyWebhookController::extractEventType()` + test in `isMediaAdded()`
- **Changing image source priority** → reorder branches in `ImageFetchingService::fetchCoverImage()`
- **New metadata field from Emby** → add to `extractMetadata()`, Blade shows it automatically (JSON column)
- **Fixing a broken API call** → open the relevant service file; each has clear single-purpose methods
- **Debugging missing images in prod** → check logs for which fallback stage succeeded/failed
