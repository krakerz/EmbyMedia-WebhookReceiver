name: emby-webhook-receiver-overview
description: Laravel app receiving webhooks from Emby media server and displaying them with metadata
---

# Project Overview

**Emby Media Webhook Receiver** is a Laravel application that receives webhook events from an Emby media server, processes them to extract rich metadata (using TVDB/TMDB APIs), and displays them on an interactive dashboard. It features auto-refreshing cover images fetched from external sources, configurable filters, and a responsive UI built with Tailwind CSS.

## Key Capabilities
- 📡 Receives JSON webhooks from Emby server (`/emby/webhook` endpoint)
- 🔐 Authenticates via `WEBHOOK_SECRET` query parameter
- 🖼️ Fetches cover images using a smart fallback chain: Emby Server → TVDB → TMDB (via IMDB ID) → Title search
- 💾 Persists events to SQLite/MySQL with full metadata and raw payload
- 🎨 Blazor-style dashboard with card grid, filters, pagination, auto-refresh timer

## Architecture Highlights

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

## Database
- **Schema:** Single `emby_webhooks` table (~15 columns)
- **Columns:** event_type, item_type/name/path, user_name, server_name, metadata (JSON), raw_payload (JSON), created_at
- **UUID routing key** — model uses `uuid()` as route key, not `id()`

## Frontend Stack
- Blade templates with Tailwind CSS v4 (`@tailwindcss/vite` plugin)
- Alpine-style inline JavaScript: cookie-based toggles for images/descriptions, auto-refresh countdown, intersection observer fade-in animations
- One reusable layout + two views (index grid, show details)

## Configuration Hotspots
`.env.example` defines these app-specific settings:
| Variable | Default | Purpose |
|--|--|--|
| `WEBHOOK_SECRET` | `` | Required query param for POST auth |
| `WEBHOOK_REFRESH_TIMER` | 30s | Auto-refresh interval on dashboard |
| `TVDB_API_KEY`, `IMDB_API_KEY` | — | External API rate-limited lookups |
| `EMBY_BASE_URL` | — | Optional direct Emby asset source |
| `SHOW_RAW_WEBHOOK_DATA`, `SHOW_FILE_LOCATION` | true | Per-feature UI toggles (many of these) |
| `WEBHOOK_ALLOWED_ITEM_TYPES` | Movie,Episode,Audio | Filterable view-set |

## Common Commands
```bash
# Bootstrap / install
composer install
npm install && npm run build
cp .env.example .env && php artisan key:generate

# Seed sample data for dev demo
php artisan migrate
php artisan db:seed --class=EmbyWebhookSeeder

# Dev server (4 concurrent processes)
php artisan serve  # or `composer dev` to also run queue + logs

# Test suite
./vendor/bin/phpunit          # same as `php artisan test`
```

## Developer Observations
1. **No TypeScript, no build pipeline beyond Vite** — keep JS edits minimal and inline; if you need more interactivity, add an `.eslintrc.*` or migrate to a component framework.
2. **Tailwind v4 syntax differs from v3** (no `content` array in `tailwind.config.js`). The project uses the new plugin-based setup — don't assume old config patterns work.
3. **One controller, many concerns** — this is fine for ~60 LOC of business logic but will become painful past that size; consider splitting into a `WebhookController` + separate `DashboardController`, or moving metadata extraction to a dedicated service class.
4. **Error handling is minimal** — the webhook handler catches `\Exception` and returns 500, but no retry policy, dead-letter queue, or structured log format. For production-scale use, introduce Laravel's job system with retry rules on `handleWebhook`.

## External APIs Consumed (no keys in code)
- Emby Server — via `EMBY_BASE_URL`, item ID resolution
- TVDB API v4 — requires `TVDB_API_KEY` for fallback image lookups
- TMDB API v3 — requires `IMDB_API_KEY` as final fallback
