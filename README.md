# Emby Webhook Dashboard

Turns your Emby server's webhooks into a live media dashboard.

<img width="1245" height="1127" alt="Dashboard screenshot" src="https://github.com/user-attachments/assets/e93dea1f-b88a-4ba7-b285-a35ebac05a55" />

## Description

Emby can notify external services when media is added or played, but the payload is just JSON — there's nothing to look at. This app receives those webhooks, resolves cover art and metadata through a fallback chain (Emby Server → TVDB → TMDB), and shows the result on a self-refreshing dashboard.

## Features

- 🖼️ Card-grid dashboard with clickable items, pagination, and server-side filtering by media type
- 🎨 Cover images resolved through a fallback chain: Emby Server → TVDB → TMDB → title search
- 🔄 Auto-refreshing dashboard that also self-heals — a small batch of stale poster/backdrop URLs is re-checked on every refresh and removed if they've stopped resolving; no cron job required
- 🌓 Light/dark theme, remembered per browser
- 👁️ Per-session toggles for images and descriptions (cookie-persisted)
- 🔗 Clickable external links (IMDb, TheTVDB, etc.) pulled straight from the webhook payload

## Installation

```bash
git clone https://github.com/krakerz/EmbyMedia-WebhookReceiver.git
cd EmbyMedia-WebhookReceiver

composer install
npm install && npm run build

cp .env.example .env
php artisan key:generate

php artisan migrate
php artisan serve
```

## Usage

In Emby, go to **Dashboard → Plugins/Notifications → Webhooks** and add:

- **URL:** `https://your-server/emby/webhook?api_key=<WEBHOOK_SECRET>`
- **Events:** at minimum, the Library events
- **Content type:** `application/json`, with "Send all properties" enabled

Add new media to your library and it should appear on the dashboard within one refresh cycle.

### Configuration

`.env.example` documents every setting; these are the ones you're most likely to touch:

| Variable | Default | Purpose |
|--|--|--|
| `WEBHOOK_SECRET` | _(empty)_ | Query-param secret required on `POST /emby/webhook`. Leaving it empty leaves the endpoint open — set one before exposing it. |
| `WEBHOOK_REFRESH_TIMER` | `30` | Dashboard auto-refresh interval, in seconds. |
| `WEBHOOK_ALLOWED_ITEM_TYPES` | `Movie,Episode,Audio` | Comma-separated item types to show/filter. Empty = show everything, no filter UI. |
| `WEBHOOK_MAX_ENTRIES` | `100` | Oldest entries beyond this count are trimmed on each refresh. `0` disables the limit. |
| `WEBHOOK_IMAGE_CHECK_BATCH` | `5` | How many poster/backdrop URLs to re-validate per dashboard load. |
| `WEBHOOK_IMAGE_RECHECK_MINUTES` | `15` | Minimum time between re-checks of the same entry. |
| `WEBHOOK_IMAGE_CHECK_STRIKES` | `3` | Consecutive failed checks before an entry is deleted (protects against a brief image-source outage). |
| `WEBHOOKS_PAGINATION_PER_PAGE` | `12` | Items per dashboard page. |
| `NEW_CARD_MINUTES` | `30` | How long the "Recently Added" badge shows after an item is created. |
| `SHOW_RAW_WEBHOOK_DATA`, `SHOW_FILE_LOCATION`, `SHOW_WEBHOOK_EVENT_DETAILS`, `SHOW_PROVIDER_IDS`, `SHOW_PREMIERE_DATE` | `true` | Toggle individual sections on the details view. |
| `EMBY_BASE_URL`, `EMBY_API_KEY` | _(empty)_ | Direct Emby asset fetching — fastest, most accurate image source when set. |
| `TVDB_API_KEY` | _(empty)_ | [Get a key](https://thetvdb.com/api-information) — TV artwork fallback. |
| `IMDB_API_KEY` | _(empty)_ | [Get a TMDB key](https://www.themoviedb.org/settings/api) — movie/TV artwork fallback, despite the env var name. |

Force an immediate full re-check of every stored image (instead of waiting for the automatic per-refresh batches) with:

```bash
php artisan webhooks:cleanup-broken-images
```

### Production deployment

- Serve over HTTPS and restrict the webhook endpoint to your Emby server's IP (firewall or nginx `allow`/`deny`).
- Give the webhook route a larger body limit and a rate limit, e.g. in nginx:
  ```nginx
  location /emby/webhook {
      client_max_body_size 10M;
      limit_req zone=webhook burst=10 nodelay;
  }
  ```
- After any `.env` or migration change: `php artisan config:cache && php artisan route:cache`.
- `APP_TIMEZONE` drives all display formatting; the DB connection itself stays UTC internally.

## FAQ

**Webhooks aren't showing up on the dashboard — how do I debug it?**
Check `storage/logs/laravel.log` for `"Emby webhook received"` entries, then test the endpoint directly:
```bash
curl -X POST "http://localhost:8000/emby/webhook?api_key=<secret>" \
  -H 'Content-Type: application/json' \
  -d '{"Event":"library.new","Item":{"Name":"Test Movie","Type":"Movie"},"Server":{"Name":"Test"}}'
```
A 401 means the secret is missing or wrong in Emby's webhook URL.

**Why are cover images missing for some items?**
Usually one of: no `TVDB_API_KEY`/`IMDB_API_KEY` set, the webhook payload has no `ProviderIds`, or the source item has no image at all. Check the logs for `Fetching cover image` / `Successfully fetched image from`.

**A broken image is stuck on the dashboard — how do I clear it?**
It clears itself within a few refresh cycles (see `WEBHOOK_IMAGE_RECHECK_MINUTES`/`WEBHOOK_IMAGE_CHECK_STRIKES` above), or run `php artisan webhooks:cleanup-broken-images` to force it immediately.

**I'm getting `SQLSTATE[HY000]: ... Unknown or incorrect time zone`.**
Your MySQL server is missing timezone tables. Load them before setting a non-UTC `APP_TIMEZONE`.

**How do I run the tests?**
```bash
php artisan test
```

---

### Notes

Built and maintained with the help of AI.

## License

MIT — see the [MIT license](https://opensource.org/licenses/MIT).
