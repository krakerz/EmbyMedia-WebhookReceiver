# Emby Webhook Dashboard

A Laravel application that receives webhooks from Emby media server and displays them on a beautiful dashboard with metadata information.

## Features

- 🎬 **Media-Focused Dashboard**: Beautiful card-based layout showcasing your media collection
- 📊 **Rich Media Information**: Displays posters, titles, summaries, ratings, and metadata
- 🎭 **Smart Categorization**: Filter by Movies, TV Shows, Music, and more
- 📝 **Comprehensive Details**: Year, runtime, genres, cast, and technical information
- 🔄 **Real-time Updates**: Auto-refresh every 30 seconds with visual indicators
- 🎯 **New Release Highlighting**: Clearly marks recently added content
- 📱 **Responsive Design**: Optimized for desktop, tablet, and mobile viewing
- ⭐ **Rating Integration**: Shows community ratings and official content ratings
- 🔗 **External Links**: Direct links to IMDB, TMDB, and other media databases
- 🎨 **Modern UI/UX**: Clean, intuitive interface designed for media enthusiasts

## Installation

1. **Clone and setup the project:**
   ```bash
   cd emby-webhook-app
   composer install
   cp .env.example .env
   php artisan key:generate
   ```

2. **Configure your database in `.env`:**
   ```
   DB_CONNECTION=sqlite
   DB_DATABASE=/absolute/path/to/database/database.sqlite
   ```

3. **Run migrations:**
   ```bash
   php artisan migrate
   ```

4. **Start the development server:**
   ```bash
   php artisan serve
   ```

## Emby Configuration

1. **In your Emby server, go to:**
   - Dashboard → Plugins → Webhooks (install if not already installed)
   - Or Dashboard → Notifications → Webhooks

2. **Add a new webhook with:**
   - **URL:** `http://your-server-ip:8000/emby/webhook`
   - **Events:** Select the events you want to monitor (recommended: Library scan completed, New media added)
   - **User filter:** (optional) Select specific users
   - **Media type filter:** (optional) Select specific media types

3. **Test the webhook:**
   - Add a new media file to your Emby library
   - Check the dashboard at `http://your-server-ip:8000`

## Usage

### Dashboard
- Visit the main page to see all webhook events
- Events are displayed with timestamps and metadata
- Click on any event to see detailed information

### Webhook Endpoint
- **URL:** `/emby/webhook`
- **Method:** POST
- **Content-Type:** application/json
- **CSRF:** Disabled for this endpoint

### API Response
The webhook endpoint returns:
```json
{
  "status": "success"
}
```

## Supported Emby Events

The application handles various Emby webhook events including:
- `library.new` - New media added
- `item.added` - Item added to library
- `playback.start` - Playback started
- `playback.stop` - Playback stopped
- And many more...

## Metadata Display

The application extracts and displays rich metadata including:
- **Basic Info:** Title, type, year, runtime
- **Ratings:** Community rating, official rating
- **Content:** Genres, tags, overview
- **Technical:** File path, container, size
- **Series Info:** Season/episode numbers (for TV shows)
- **Provider IDs:** IMDB, TMDB, etc.

## File Structure

```
emby-webhook-app/
├── app/
│   ├── Http/Controllers/
│   │   └── EmbyWebhookController.php
│   └── Models/
│       └── EmbyWebhook.php
├── database/
│   └── migrations/
│       └── create_emby_webhooks_table.php
├── resources/views/
│   ├── layouts/
│   │   └── app.blade.php
│   └── webhooks/
│       ├── index.blade.php
│       └── show.blade.php
└── routes/
    └── web.php
```

## Database Schema

The `emby_webhooks` table stores:
- `event_type` - Type of webhook event
- `item_type` - Type of media item (Movie, Episode, etc.)
- `item_name` - Name of the media item
- `item_path` - File path of the media
- `user_name` - User associated with the event
- `server_name` - Emby server name
- `metadata` - JSON field with rich metadata
- `raw_payload` - Complete webhook payload

## Customization

### Adding New Event Types
Edit `EmbyWebhookController::extractEventType()` to handle additional event types.

### Modifying Metadata Extraction
Update `EmbyWebhookController::extractMetadata()` to extract additional fields.

### Styling Changes
The application uses Tailwind CSS. Modify the Blade templates to change the appearance.

## Troubleshooting

### Webhooks Not Appearing
1. Check Emby webhook configuration
2. Verify the webhook URL is accessible
3. Check Laravel logs: `tail -f storage/logs/laravel.log`

### Database Issues
1. Ensure the database file exists and is writable
2. Run migrations: `php artisan migrate`
3. Check database permissions

### CSRF Token Errors
The webhook endpoint is excluded from CSRF protection in `bootstrap/app.php`.

## Security Notes

- The webhook endpoint is publicly accessible (no authentication)
- Consider adding IP whitelisting for production use
- Use HTTPS in production environments
- Regularly backup your webhook data

## License

This project is open-sourced software licensed under the MIT license.