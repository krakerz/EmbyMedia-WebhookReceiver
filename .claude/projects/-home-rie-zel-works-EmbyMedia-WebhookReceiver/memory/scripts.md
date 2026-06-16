name: emby-webhook-scripts-and-workflows
description: Common scripts, commands, and development workflows for the Emby Webhook Receiver project
---

# Scripts & Development Workflows

## Common Commands Reference

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

# Production-ready migration
php artisan config:cache        # compile routes/views/config
php artisan route:cache
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

## Debugging Workflows

### Problem: Webhooks not appearing in dashboard

**Step 1 — Verify endpoint is receiving requests:**
```bash
# Check Laravel logs for webhook entries
tail -100 storage/logs/laravel.log | grep "webhook received"
```

Expected output should include `"Emby webhook received"` log with payload.

**Step 2 — Test the endpoint manually:**
```bash
curl -X POST http://localhost:8000/emby/webhook \
  --header 'Content-Type: application/json' \
  -d '{
    "Event": "library.new",
    "Item": {"Name": "Test Movie", "Type": "Movie"},
    "Server": {"Name": "Test"}
  }'
```

Expected response: `{"status":"success"}` with HTTP 200. If you get 401, check `WEBHOOK_SECRET` is set or not in query string.

**Step 3 — Confirm DB persistence:**
```bash
php artisan tinker
>>> EmbyWebhook::latest()->get()
```

Should show new row with populated metadata JSON column.

---

### Problem: Cover images not loading for TV shows

**Root Cause is usually one of three things, in order of frequency:**

1. **Missing API keys** — without `TVDB_API_KEY` or `IMDB_API_KEY`, fallback chain breaks at step 2/3
   - Quick fix: get a free TMDB key (5 min), set in `.env`: `IMDB_API_KEY=sk-xxxxx`

2. **No provider IDs in webhook** — Emby must send `ProviderIds.Tvdb` or `ProviderIds.IMDB` for lookup to work
   - Check raw_payload JSON: `"ProviderIds": {"Tvdb": "123456"}` is required

3. **Wrong item type branch** — episodes need different API call than movies
   - Service checks `in_array($itemType, ['Episode', 'Season', 'Series'])` to route correctly

**Diagnostic command:** Check logs for which fallback stage succeeded:
```bash
grep "Fetching cover image" storage/logs/laravel.log | tail -5
grep "Successfully fetched image from" storage/logs/laravel.log | tail -5
```

---

### Problem: Images fetching but not showing in UI

**Check cookie toggles** — the index view has two checkboxes that default to enabled but persist state:
- `getCookie('show_images')` → if null, defaults to false (then checkbox checked)
- `getCookie('show_descriptions')` → same behavior

If a user disabled images and refreshed, descriptions disappear too. Debug by opening browser DevTools Console — the initialization logs should print `"Emby Media Dashboard loaded with toggle controls"`.

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
