name: emby-webhook-environment-and-config
description: Environment variables, configuration options, and deployment settings for the Emby Webhook Receiver
---

# Configuration & Environment Variables

## Core Application Settings (`.env`)

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

---

## Webhook-Specific Settings (`.env`)

### Timer & Refresh
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

## Common Configuration Mistakes to Avoid

| Mistake | Symptom | Fix |
|--|--|--|
| Missing `WEBHOOK_SECRET` in query param | Consistent 401 errors on every webhook POST | Add secret validation logging, or set empty in `.env` and accept open endpoint (NOT RECOMMENDED) |
| Relative SQLite path (`database.sqlite`) | File not found on deployment with different working directory | Use absolute path: `/var/www/app/database.sqlite` |
| `APP_TIMEZONE=Asia/Jakarta` without MySQL tz data | Database errors, wrong timestamps | Install timezone tables OR use UTC (recommended for multi-region apps) |
| Empty `WEBHOOK_ALLOWED_ITEM_TYPES` + filter UI shows | Confusing UX — no filters but all types shown in DB | Either set at least one type OR leave empty and remove filter buttons from view manually |
