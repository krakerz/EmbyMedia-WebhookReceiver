# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

---

## Project Overview

**Emby Media Webhook Receiver** is a Laravel application that receives webhook events from an Emby media server, processes them to extract rich metadata (using TVDB/TMDB APIs), and displays them on an interactive dashboard. It features auto-refreshing cover images fetched from external sources, configurable filters, and a responsive UI built with Tailwind CSS.

### Key Capabilities
- 📡 Receives JSON webhooks from Emby server (`/emby/webhook` endpoint)
- 🔐 Authenticates via `WEBHOOK_SECRET` query parameter
- 🖼️ Fetches cover images using a smart fallback chain: Emby Server → TVDB → TMDB (via IMDB ID) → Title search
- 💾 Persists events to SQLite/MySQL with full metadata and raw payload
- 🎨 Beautiful dashboard with card grid, filters, pagination, auto-refresh timer

---

## Architecture Overview

### Single Responsibility by Controller

The entire application is one controller (`EmbyWebhookController`) — a deliberate choice for a narrow domain. It has three actions: `index()` (dashboard), `show()` (details), and `handleWebhook()` (ingest). No routing complexity, no multi-controller sprawl.

### Image Fetching Pipeline

`ImageFetchingService` orchestrates the cover image resolution chain with 4 sources tried in order:
1. **Emby Server** — Uses item ID + image tag for exact asset caching
2. **TVDB** — For TV content (episodes/seasons) via Tvdb provider IDs
3. **TMDB** — Via IMDB provider IDs, or as final fallback with title search
4. Each source is a dedicated service class (`EmbyService`, `TvdbService`, `ImdbService`)

### Data Flow
```
POST /emby/webhook → EmbyWebhookController::handleWebhook()
    ↓
  Extract structured data (event, item, user, server) + raw metadata
    ↓
  For library.new/item.added: ImageFetchingService runs the fetch chain
    ↓
  Persist to emby_webhooks table with JSON columns for flexibility
```

### Stateless Event Storage

The `emby_webhooks` table stores events as a flat event log — no foreign keys, no relationships beyond a self-referential UUID (added via migration). Each row is an immutable snapshot. Queries are always: "show me X items of type Y, page Z".

---

## Core Files to Know

### Entry Points
| File | Purpose |
|--|--|
| `routes/web.php` | Routes — only 3 routes, memorize them: index, show, webhook receiver |
| `.env.example` | All configurable settings with defaults |

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

## Common Commands

### Installation & Setup (One-Time)
```bash
# Fresh install from scratch
git clone <repo> && cd EmbyMedia-WebhookReceiver
composer install
npm install && npm run build

# Environment setup
cp .env.example .env
php artisan key:generate
touch database/database.sqlite   # SQLite default for dev
php artisan migrate

# Seed sample data for dev demo
php artisan db:seed --class=EmbyWebhookSeeder
```

### Daily Development Commands
```bash
# Start PHP server (default)
php artisan serve                # listens on http://localhost:8000

# Multi-process dev environment (4 panels)
composer dev                     # runs server + queue + logs + vite in parallel
                                # colored output: [server] [queue] [logs] [vite]

# Tailored queue listener for webhook bursts
php artisan queue:listen --tries=1   # retry once on failure, then discard
```

### Database Operations
```bash
# Seed sample data for dev demo (only 4 rows)
php artisan db:seed --class=EmbyWebhookSeeder

# Fresh migration + seed (reset dev DB)
php artisan migrate:fresh --seed

# Migration inspection
php artisan migrate:status        # show applied/pending migrations
php artisan tinker                # PHP REPL, try: EmbyWebhook::count()
```

### Testing Suite
```bash
# Full test suite (clears config cache first)
./vendor/bin/phpunit              # same as `php artisan test`

# Run a single test file
./vendor/bin/phpunit tests/Feature/ImageFetchingTest.php

# Debug failing tests with stack trace
./vendor/bin/phpunit --display-errors=both

# CI-friendly: exit 0 only on full success
```

### Log Management (Production)
```bash
# Real-time Laravel log tailing (dev)
php artisan pail                  # shows queue + webhook processing logs
tail -f storage/logs/laravel.log

# Search logs with regex
grep "Unauthorized.*webhook" storage/logs/laravel.log | head -20
```

---

## Configuration Options

### Essential Settings — Must Configure Before Production
| Variable | Default | Purpose | Where to Set |
|--|--|--|--|
| `WEBHOOK_SECRET` | `` | **REQUIRED** query param for POST auth. Generate with: `openssl rand -hex 32` | `.env`, commit hash only |
| `APP_TIMEZONE` | UTC | Laravel's default timezone — affects date formatting throughout app | Match your server, e.g., `Asia/Jakarta` |
| `DB_CONNECTION` | sqlite | Switch to mysql/postgres for production | Production: `mysql` |

### Database Configuration
```env
# SQLite (dev / small deployments) — no setup needed
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/database.sqlite  # use full path, not relative

# MySQL/PostgreSQL (production recommended)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=emby_webhook
DB_USERNAME=db_user
DB_PASSWORD=strong_password_here
```

**⚠️ Timezone gotcha:** MySQL 8+ requires timezone tables loaded. If you see `SQLSTATE[HY000]: General error: 1298 Unknown or incorrect time zone`, install timezone data first before setting `APP_TIMEZONE`.

### Webhook-Specific Settings (`.env`)
| Variable | Default | Description |
|--|--|--|
| `WEBHOOK_REFRESH_TIMER` | 30 | Auto-refresh interval in **seconds** for the dashboard. Accepts any integer — app formats display as hours/minutes/seconds intelligently |

### Display Toggle Settings (Boolean: true/false)
| Variable | Default | Controls... |
|--|--|--|
| `SHOW_RAW_WEBHOOK_DATA` | true | Show raw webhook JSON in details view |
| `SHOW_FILE_LOCATION` | true | Show file path (`item_path`) section |
| `SHOW_WEBHOOK_EVENT_DETAILS` | true | Show event metadata (user, server) |
| `SHOW_PROVIDER_IDS` | true | Show external ID references (IMDB, TVDB IDs) in details view |
| `SHOW_PREMIERE_DATE` | true | Display premiere date badge on cards/details |

### UI & Filtering
| Variable | Default | Effect |
|--|--|--|
| `WEBHOOKS_PAGINATION_PER_PAGE` | 12 | Items shown per page. Affects grid layout: 6=3 cols, 9=4×5, 12=standard |
| `NEW_CARD_MINUTES` | 30 | How long the ✨ NEW badge stays visible (minutes since creation) |

### Item Type Filtering
```env
# Comma-separated list. Empty = show all types (no filter UI shown)
# Available Emby types: Movie, Episode, Season, Series, Album, MusicAlbum, Audio, Video
WEBHOOK_ALLOWED_ITEM_TYPES="Movie,Episode,Audio"

# Effect:
# • Filter buttons only created for allowed types in index view
# • Dashboard query filters to these item_types automatically
# • Pagination resets to page 1 when filter applied (server-side)
```

---

## External API Configuration

### TVDB API v4 (Fallback for TV content)
Get key from: https://thetvdb.com/api-information

```env
TVDB_API_KEY=your_tvdb_api_key_here           # optional — enables image fallback
TVDB_API_URL=https://api4.thetvdb.com/v4      # default, rarely needs change
```

### TMDB API v3 / IMDB (Fallback for movies & TV)
Get key from: https://www.themoviedb.org/settings/api

```env
IMDB_API_KEY=your_tmdb_api_key_here           # optional — enables image fallback
IMDB_API_URL=https://api.themoviedb.org/3     # default, rarely needs change
```

**Rate limit note:** Both APIs are free but rate-limited. Consider caching responses if you have high traffic (not currently implemented).

---

## Emby Server Integration Settings

### Direct Asset Fetching (Primary Image Source)
When configured, the app fetches images directly from your Emby server using item ID + image tag (fastest, most accurate):

```env
EMBY_BASE_URL=http://your-emby-server:8096    # required for cover image fetching
EMBY_API_KEY=your_emby_api_key_here           # optional — enhances metadata extraction
```

**Without these:** App falls back to TVDB → TMDB chain, which is slower but works without Emby credentials.

---

## Frontend / Build Settings

### Vite Configuration (in `vite.config.js`)
```javascript
plugins: [
  laravel({ input: ['resources/css/app.css', 'resources/js/app.js'], refresh: true }),
  tailwindcss(),
]
```
- **`refresh: true`** — hot module replacement (HMR) enabled for dev
- No separate `app.js` needed — inline script in Blade is the only JS

---

## Production Deployment Configuration

### Nginx Security Headers (recommended)
Add these to your server block:
```nginx
add_header X-Frame-Options "SAMEORIGIN" always;
add_header X-XSS-Protection "1; mode=block" always;
add_header X-Content-Type-Options "nosniff" always;
add_header Referrer-Policy "no-referrer-when-downgrade" always;

# Webhook endpoint — allow larger bodies, rate limit aggressively
location /emby/webhook {
    client_max_body_size 10M;
    limit_req zone=webhook burst=5 nodelay;
}

# Static assets cache aggressively  
location ~* \.(js|css|png|jpg)$ { expires 1y; add_header Cache-Control "public, immutable"; }
```

### PHP-FPM Optimization
For high webhook volume:
```nginx
location ~ \.php$ {
    fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
    
    # Increase timeouts for large webhook payloads
    fastcgi_read_timeout 300;      # 5 min — default is 60s
    fastcgi_send_timeout 300;      
}
```

### Caching Strategies (Optional Improvements)

**Route caching:**
```bash
php artisan config:cache
php artisan route:cache
# Rebuild cache after any .env change or new migration
```

**View caching:**
```bash
php artisan view:cache     # caches compiled Blade templates
php artisan view:clear     # when template changes cause 500s
```

**Redis-backed session/cache (optional):**
If using Redis, replace database cache in `.env`:
```env
CACHE_STORE=redis           # requires phpredis extension installed
SESSION_DRIVER=redis        # faster for concurrent users
```

---

## Configuration Change Checklist

After modifying any of these files, always do:
1. **`.env` change** → `php artisan config:clear` (or reboot) — Laravel caches .env at bootstrap
2. **Migration added** → `php artisan migrate --force` in production (no migrations table means no auto-detect)
3. **Route added** → `php artisan route:cache` if using cache
4. **API key rotated** → verify logs show successful image fetch after 1 min

---

## Testing & Debugging

### Test Suite Overview

#### Running Tests
```bash
# Full test suite (same as `php artisan test`)
./vendor/bin/phpunit

# Run a single file
./vendor/bin/phpunit tests/Feature/ImageFetchingTest.php

# Debug with verbose output and stack traces
./vendor/bin/phpunit --display-errors=both

# CI mode — exit 0 only on full success
./vendor/bin/phpunit --no-coverage
```

#### Test Architecture
| File | Type | Purpose |
|--|--|--|
| `tests/TestCase.php` | Base case | Extends Laravel's `TestCase`, no overrides needed |
| `tests/Feature/ImageFetchingTest.php` | Feature test | Verifies image fetching chain with mocked API responses |
| `tests/Feature/EmbyImageFetchingTest.php` | Feature test | Tests webhook processing through full controller→service pipeline |

#### Writing New Tests — Pattern
```php
<?php
namespace Tests\Feature;

use App\Models\EmbyWebhook;
use Illuminate\Foundation\Testing\RefreshDatabase;   // fresh DB per test
use Illuminate\Support\Facades\Http;                   // mock HTTP calls
use Tests\TestCase;

class MyNewTest extends TestCase
{
    use RefreshDatabase;  // ← always include for isolated tests

    public function test_scenario(): void
    {
        // 1. Mock external API responses FIRST (before they're called)
        Http::fake([
            'api.themoviedb.org/3/*' => Http::response(['results' => []], 200),
        ]);

        // 2. Trigger the action under test
        $payload = ['Event' => 'library.new', ...];
        $response = $this->postJson('/emby/webhook', $payload);

        // 3. Assert outcomes: HTTP status + side effects (DB, logs)
        $response->assertStatus(200);
        $webhook = EmbyWebhook::first();   // ← verifies persistence happened
        $this->assertEquals('library.new', $webhook->event_type);
    }

    protected function tearDown(): void
    {
        Http::verify();  // validates all faked requests were made (catches leaks)
        parent::tearDown();
    }
}
```

### Debugging Workflows

#### Problem: Webhooks not appearing in dashboard

**Step 1 — Confirm endpoint is receiving requests:**
```bash
# Check logs for webhook entries (last 50 lines)
tail -50 storage/logs/laravel.log | grep "webhook received"
```
Expected output should include the payload JSON. If nothing shows, Emby isn't sending.

**Step 2 — Manual POST test:**
```bash
curl -X POST http://localhost:8000/emby/webhook \
  --header 'Content-Type: application/json' \
  -d '{
    "Event": "library.new",
    "Item": {"Name": "Test Movie", "Type": "Movie"},
    "Server": {"Name": "Test"}
  }'
```
Expected: `{"status":"success"}` with HTTP 200. If you get 401, the secret is missing or wrong in query string.

**Step 3 — Verify DB persistence:**
```bash
php artisan tinker
>>> EmbyWebhook::latest()->get()
```
Should show new row with populated metadata JSON column.

#### Problem: Cover images not loading for TV shows

**Root cause is usually one of three things, in order of frequency:**

1. **Missing API keys** — without `TVDB_API_KEY` or `IMDB_API_KEY`, fallback chain breaks at step 2/3
   - Quick fix: get a free TMDB key (5 min), set in `.env`: `IMDB_API_KEY=sk-xxxxx`

2. **No provider IDs in webhook** — Emby must send `"ProviderIds": {"Tvdb": "123456"}` for lookup to work
   - Check raw_payload JSON; if empty, your Emby plugin isn't sending full metadata

3. **Wrong item type branch** — episodes need different API call than movies
   - Service checks `in_array($itemType, ['Episode', 'Season', 'Series'])` to route correctly

**Diagnostic command:** Check logs for which fallback stage succeeded:
```bash
grep "Fetching cover image" storage/logs/laravel.log | tail -5
grep "Successfully fetched image from" storage/logs/laravel.log | tail -5
```

#### Problem: Images fetching but not showing in UI

**Check cookie toggles** — the index view has two checkboxes that default to enabled but persist state:
- `getCookie('show_images')` → if null, defaults to false (then checkbox checked)
- `getCookie('show_descriptions')` → same behavior

If a user disabled images and refreshed, descriptions disappear too. Debug by opening browser DevTools Console — the initialization logs should print `"Emby Media Dashboard loaded with toggle controls"`.

#### Problem: Auto-refresh not working

**Symptoms:** Page stays static after N seconds even though webhooks arrived.

**Diagnosis steps:**
1. Open browser DevTools → Network tab → filter "All"
2. Look for reload events every ~30s (or your configured timer)
3. If no reload, check JavaScript console for errors — likely a JS exception breaking the countdown script at bottom of layout

The refresh timer lives in `layouts/app.blade.php` lines 136-159:
```javascript
let refreshCountdown = {{ $refreshTimer ?? 30 }};
setInterval(() => { /* ... */ }, 1000);
if (refreshCountdown <= 0) {
    document.body.style.opacity = '0.9';
    window.location.reload();   // ← this line can throw if DOM is partially loaded
}
```

---

## Adding New Functionality: Quick Patterns

### Add a new webhook event type handler
```php
// In EmbyWebhookController::handleWebhook()
if ($eventType === 'user.created') {
    Log::info('New user created', ['username' => $userData['name']]);
    // dispatch job, send notification, etc.
}
```

### Add a new metadata field from webhook payload
No code change needed beyond `extractMetadata()` — just add another array item:
```php
private function extractMetadata(array $payload): array
{
    if (isset($payload['Item'])) {
        return [
            ...$this->extractMetadata($payload),
            'my_new_field' => $payload['Item']['NewField'] ?? null,
        ];
    }
}
```

### Add a new image source
Insert a new branch in `ImageFetchingService::fetchCoverImage()` before the final fallback. The framework already handles ordering (top→bottom).

---

## Deployment Checklist

Before deploying to production:

- [ ] Set `APP_DEBUG=false` and `LOG_LEVEL=error` in `.env`
- [ ] Generate unique `WEBHOOK_SECRET` — document it somewhere secure
- [ ] Configure firewall to allow only Emby server IP to hit `/emby/webhook`
- [ ] Verify nginx config has `client_max_body_size 10M` on webhook route
- [ ] Run `php artisan migrate:fresh --seed` with real sample data
- [ ] Check logs: `tail -f storage/logs/laravel.log | grep "webhook"` for 5 min

---

## Common Configuration Mistakes to Avoid

| Mistake | Symptom | Fix |
|--|--|--|
| Missing `WEBHOOK_SECRET` in query param | Consistent 401 errors on every webhook POST | Add secret validation logging, or set empty in `.env` and accept open endpoint (NOT RECOMMENDED) |
| Relative SQLite path (`database.sqlite`) | File not found on deployment with different working directory | Use absolute path: `/var/www/app/database.sqlite` |
| `APP_TIMEZONE=Asia/Jakarta` without MySQL tz data | Database errors, wrong timestamps | Install timezone tables OR use UTC (recommended for multi-region apps) |
| Empty `WEBHOOK_ALLOWED_ITEM_TYPES` + filter UI shows | Confusing UX — no filters but all types shown in DB | Either set at least one type OR leave empty and remove filter buttons from view manually |
