# 🚀 Quick Start Guide

## Prerequisites
- PHP 8.2 or higher
- Composer
- Node.js (optional, for development)

## Installation Steps

### 1. Clone and Setup
```bash
git clone git@github.com:krakerz/EmbyMedia-WebhookReceiver.git
cd EmbyMedia-WebhookReceiver
composer install
cp .env.example .env
php artisan key:generate
```

### 2. Database Setup
```bash
# Create SQLite database
touch database/database.sqlite

# Run migrations
php artisan migrate

# (Optional) Add sample data
php artisan db:seed --class=EmbyWebhookSeeder
```

### 3. Start the Server
```bash
php artisan serve
```

Your dashboard will be available at: `http://localhost:8000`

## 🔧 Emby Configuration

1. **Access Emby Admin Dashboard**
   - Go to your Emby server admin panel
   - Navigate to **Plugins** → **Webhooks** (install if needed)

2. **Add Webhook**
   - **URL**: `http://your-server-ip:8000/emby/webhook`
   - **Events**: Select desired events (recommended: "Library scan completed", "New media added")
   - **Content Type**: `application/json`

3. **Test the Setup**
   - Add new media to your Emby library
   - Check the dashboard for new entries

## 📱 Features Overview

### Main Dashboard
- **Media Cards**: Beautiful grid layout with posters and metadata
- **Smart Filters**: Filter by Movies, TV Shows, Music, or All Media
- **Live Updates**: Auto-refreshes every 30 seconds
- **Responsive**: Works on desktop, tablet, and mobile

### Media Details
- **Rich Information**: Comprehensive metadata display
- **External Links**: Direct links to IMDB, TMDB
- **Technical Details**: File information, container types, sizes
- **Series Support**: Season/episode information for TV shows

## 🛠️ Customization

### Environment Variables
Edit `.env` file for custom configuration:
```env
APP_NAME="Your Media Dashboard"
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/database.sqlite
```

### Styling
The application uses Tailwind CSS. Modify the Blade templates in `resources/views/` to customize the appearance.

## 🔍 Troubleshooting

### Webhooks Not Appearing
1. Verify Emby webhook URL is correct
2. Check Laravel logs: `tail -f storage/logs/laravel.log`
3. Ensure the webhook endpoint is accessible from Emby server

### Permission Issues
```bash
chmod -R 775 storage bootstrap/cache
```

### Database Issues
```bash
php artisan migrate:fresh --seed
```

## 📚 API Endpoints

- `GET /` - Main dashboard
- `GET /webhook/{id}` - Media details
- `POST /emby/webhook` - Webhook receiver (for Emby)

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Submit a pull request

## 📄 License

This project is open-sourced software licensed under the MIT license.