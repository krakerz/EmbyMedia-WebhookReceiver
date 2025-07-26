# Emby Webhook Dashboard

A Laravel application that receives webhooks from Emby media server and displays them on a beautiful dashboard with metadata information and cover images fetched from TVDB and TMDB.

## Features

- 🎬 **Media Dashboard**: Beautiful grid layout showing your latest media additions with fully clickable cards
- 📊 **Detailed Metadata**: Comprehensive information about movies, TV shows, and episodes
- 🖼️ **Cover Images**: Automatically fetches cover images from TVDB and TMDB
- ⏱️ **Configurable Auto-refresh**: Customizable timer for dashboard updates
- 🔍 **Provider Integration**: Support for TVDB and IMDB/TMDB metadata providers
- 📱 **Responsive Design**: Works perfectly on desktop and mobile devices
- 🎯 **Real-time Updates**: Live webhook processing with instant dashboard updates
- 🖱️ **Interactive Cards**: Click anywhere on a media card to view detailed information

## Installation

1. **Clone the repository:**
   ```bash
   git clone https://github.com/krakerz/EmbyMedia-WebhookReceiver.git
   cd EmbyMedia-WebhookReceiver
   ```

2. **Install dependencies:**
   ```bash
   composer install
   npm install && npm run build
   ```

3. **Set up environment:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Configure your .env file:**
   ```env
   # Webhook Configuration
   WEBHOOK_REFRESH_TIMER=30
   SHOW_RAW_WEBHOOK_DATA=true
   SHOW_FILE_LOCATION=true
   SHOW_WEBHOOK_EVENT_DETAILS=true

   # Emby server configuration for cover images
   EMBY_BASE_URL=http://your-emby-server:8096
   EMBY_API_KEY=your_emby_api_key_here

   # External API Configuration (fallback sources)
   TVDB_API_KEY=your_tvdb_api_key_here
   IMDB_API_KEY=your_tmdb_api_key_here
   ```

5. **Set up database:**
   ```bash
   php artisan migrate
   ```

6. **Start the server:**
   ```bash
   php artisan serve
   ```

## Nginx Configuration

For production deployment, here's a complete nginx configuration example:

### Site Configuration (`/etc/nginx/sites-available/emby-webhook`)

```nginx
server {
    listen 80;
    server_name your-domain.com;
    
    # Redirect HTTP to HTTPS
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name your-domain.com;
    
    # SSL Configuration
    ssl_certificate /path/to/your/certificate.crt;
    ssl_certificate_key /path/to/your/private.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512:ECDHE-RSA-AES256-GCM-SHA384:DHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers off;
    ssl_session_cache shared:SSL:10m;
    
    # Document root
    root /var/www/emby-webhook/public;
    index index.php index.html;
    
    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;
    
    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_proxied expired no-cache no-store private must-revalidate auth;
    gzip_types text/plain text/css text/xml text/javascript application/javascript application/xml+rss application/json;
    
    # Main location block
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    # PHP-FPM configuration
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;  # Adjust PHP version as needed
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
        
        # Increase timeouts for webhook processing
        fastcgi_read_timeout 300;
        fastcgi_send_timeout 300;
    }
    
    # Webhook endpoint with specific configuration
    location /emby/webhook {
        # Allow larger request bodies for webhook payloads
        client_max_body_size 10M;
        
        # Rate limiting (adjust as needed)
        limit_req zone=webhook burst=10 nodelay;
        
        try_files $uri /index.php?$query_string;
    }
    
    # Static assets caching
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
    }
    
    # Deny access to sensitive files
    location ~ /\. {
        deny all;
    }
    
    location ~ /\.env {
        deny all;
    }
    
    location ~ /storage/ {
        deny all;
    }
    
    location ~ /bootstrap/cache/ {
        deny all;
    }
    
    # Logs
    access_log /var/log/nginx/emby-webhook.access.log;
    error_log /var/log/nginx/emby-webhook.error.log;
}
```

### Rate Limiting Configuration (`/etc/nginx/nginx.conf`)

Add this to the `http` block in your main nginx configuration:

```nginx
# Rate limiting for webhook endpoint
limit_req_zone $binary_remote_addr zone=webhook:10m rate=30r/m;
```

### Deployment Steps

1. **Copy your application to the server:**
   ```bash
   sudo cp -r /path/to/EmbyMedia-WebhookReceiver /var/www/emby-webhook
   sudo chown -R www-data:www-data /var/www/emby-webhook
   sudo chmod -R 755 /var/www/emby-webhook
   sudo chmod -R 775 /var/www/emby-webhook/storage
   sudo chmod -R 775 /var/www/emby-webhook/bootstrap/cache
   ```

2. **Enable the site:**
   ```bash
   sudo ln -s /etc/nginx/sites-available/emby-webhook /etc/nginx/sites-enabled/
   sudo nginx -t
   sudo systemctl reload nginx
   ```

3. **Set up environment for production:**
   ```bash
   cd /var/www/emby-webhook
   sudo -u www-data cp .env.example .env
   sudo -u www-data php artisan key:generate
   sudo -u www-data php artisan migrate --force
   sudo -u www-data php artisan config:cache
   sudo -u www-data php artisan route:cache
   sudo -u www-data php artisan view:cache
   ```

4. **Configure environment variables:**
   ```env
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://your-domain.com
   
   # Database configuration
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=emby_webhook
   DB_USERNAME=your_db_user
   DB_PASSWORD=your_db_password
   
   # Other configurations...
   ```

### Security Considerations

- **Firewall**: Restrict access to the webhook endpoint to your Emby server IP
- **SSL**: Always use HTTPS in production
- **Rate Limiting**: Implement rate limiting to prevent abuse
- **Monitoring**: Set up log monitoring for the webhook endpoint
- **Backup**: Regular database backups of webhook data

## API Keys Setup

### Emby Server Configuration (Primary Image Source)
1. Configure your Emby server URL in `.env` as `EMBY_BASE_URL`
2. Optionally add your Emby API key as `EMBY_API_KEY` for enhanced metadata
3. Images will be fetched directly from your Emby server using the format:
   ```
   {{EMBY_BASE_URL}}/emby/Items/{{item_id}}/Images/Primary?tag={{image_tag}}&quality=90
   ```

### TVDB API Key (Fallback)
1. Visit [TVDB API Information](https://thetvdb.com/api-information)
2. Create an account and request an API key
3. Add your API key to `.env` as `TVDB_API_KEY`

### TMDB API Key (Fallback)
1. Visit [TMDB API Settings](https://www.themoviedb.org/settings/api)
2. Create an account and request an API key
3. Add your API key to `.env` as `IMDB_API_KEY`

## Emby Configuration

1. **Access Emby Admin Dashboard:**
   - Dashboard → Plugins → Webhooks (install if not already installed)
   - Or Dashboard → Notifications → Webhooks

2. **Add a new webhook with:**
   - **URL:** `http://your-server-ip:8000/emby/webhook`
   - **Events:** Select the events you want to track (recommended: Library events)
   - **Request content type:** `application/json`
   - **Send all properties:** Enabled

3. **Test the webhook:**
   - Add new media to your Emby library
   - Check the dashboard for new entries

## Usage

- Visit the main page to see all webhook events
- Click anywhere on any media card to see detailed information
- The dashboard auto-refreshes based on your configured timer
- Cover images are automatically fetched and cached
- Use the filter buttons to show specific media types (Movies, TV Shows, Music)

### Webhook Endpoint
- **URL:** `/emby/webhook`
- **Method:** `POST`
- **Content-Type:** `application/json`
- **Authentication:** None (configure firewall rules as needed)

### API Response
The webhook endpoint returns:
```json
{
  "status": "success"
}
```

### Show Provider IDs Section

You can control the visibility of the "Provider IDs" section in the media details view via the `.env` configuration.

Add the following variable to your `.env` file:

```env
SHOW_PROVIDER_IDS=true
```

Set it to `false` to hide the Provider IDs section.

This setting is also available in the `.env.example` file for reference.
## Configuration Options

### Timer Configuration
Configure the auto-refresh timer in your `.env` file:
```env
WEBHOOK_REFRESH_TIMER=30  # Refresh every 30 seconds
```

### Image Fetching Priority
The application fetches cover images in the following order:
1. **Emby Server** (primary source using item ID and image tags)
2. **TVDB** (fallback for TV shows, seasons, episodes)
3. **TMDB** (fallback using IMDB ID)
4. **TMDB Search** (final fallback using title and year)

### Raw Webhook Data Display
Control whether raw webhook data is shown in the interface:
```env
SHOW_RAW_WEBHOOK_DATA=true  # Show raw data section (default)
SHOW_RAW_WEBHOOK_DATA=false # Hide raw data section
```

### Interface Display Options
Control which sections are visible in the webhook details:
```env
SHOW_FILE_LOCATION=true          # Show file path section (default)
SHOW_WEBHOOK_EVENT_DETAILS=true  # Show event details section (default)
```

### External Provider Links
The application automatically creates clickable links from the `ExternalUrls` provided in the webhook response. These URLs are content-specific and provided directly by Emby:

- **Dynamic URLs**: Uses actual URLs from webhook `Item.ExternalUrls` array
- **Content-Specific**: URLs are tailored to the specific content (movie, episode, etc.)
- **Multiple Providers**: Supports any external provider that Emby has configured
- **Provider IDs**: Also displays provider IDs as reference data (non-clickable)

Example external URLs from webhook:
```json
"ExternalUrls": [
    {
        "Name": "IMDb",
        "Url": "https://www.imdb.com/title/tt37616507"
    },
    {
        "Name": "TheTVDB", 
        "Url": "https://thetvdb.com/?tab=episode&id=11178926"
    }
]
```

All external links open in a new browser tab.

## Supported Events

The application handles various Emby webhook events including:
- `library.new` - New media added to library
- `item.added` - Item added to library
- `playback.start` - User started playback
- `playback.stop` - User stopped playback
- `user.created` - New user created
- And many more...

## File Structure

```
EmbyMedia-WebhookReceiver/
├── app/
│   ├── Http/Controllers/
│   │   └── EmbyWebhookController.php
│   ├── Models/
│   │   └── EmbyWebhook.php
│   └── Services/
│       ├── TvdbService.php
│       ├── ImdbService.php
│       ├── EmbyService.php
│       └── ImageFetchingService.php
├── database/migrations/
│   └── create_emby_webhooks_table.php
├── resources/views/
│   └── webhooks/
└── tests/Feature/
    ├── ImageFetchingTest.php
    └── EmbyImageFetchingTest.php
```

## Database Schema

The `emby_webhooks` table stores:
- `event_type` - Type of webhook event
- `item_type` - Type of media (Movie, Episode, etc.)
- `item_name` - Name of the media item
- `item_path` - File path on server
- `user_name` - User who triggered the event
- `server_name` - Emby server name
- `metadata` - Extracted metadata including cover images
- `raw_payload` - Complete webhook payload

## Customization

### Adding New Event Types
Edit `EmbyWebhookController::extractEventType()` to handle additional event types.

### Extracting Additional Metadata
Update `EmbyWebhookController::extractMetadata()` to extract additional fields.

### Custom Image Sources
Extend the `ImageFetchingService` to add support for additional image providers.

## Troubleshooting

### Webhooks Not Appearing
1. Check Emby webhook configuration
2. Verify the webhook URL is accessible
3. Check Laravel logs: `storage/logs/laravel.log`
4. Ensure proper firewall configuration

### Images Not Loading
1. Verify API keys are correctly configured
2. Check network connectivity to TVDB/TMDB
3. Review application logs for API errors
4. Ensure provider IDs exist in webhook data

### Performance Issues
1. Consider caching API responses
2. Implement image caching strategy
3. Monitor API rate limits

## Security Notes

- The webhook endpoint is publicly accessible (no authentication)
- Configure firewall rules to restrict access to your Emby server
- API keys are stored in environment variables
- Regularly backup your webhook data

## Testing

Run the test suite:
```bash
php artisan test
```

The tests include:
- Webhook processing functionality
- Image fetching from TVDB and TMDB
- Timer configuration validation

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests for new functionality
5. Submit a pull request

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).