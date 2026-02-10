# AppSignals Analytics Platform - Backend

Self-hosted mobile analytics for iOS apps with event tracking and crash reporting.

## Requirements

- PHP 8.2+
- MySQL 8.0+ or MariaDB 10.6+
- Composer

## Quick Start

```bash
# Install dependencies
composer install

# Run the guided installer (creates .env, APP_KEY, runs migrations)
php artisan appsignals:install --seed

# Start development server
php artisan serve
```

Manual setup (optional):

```bash
cp .env.example .env
php artisan key:generate

# Configure database in .env
# DB_DATABASE=appsignals
# DB_USERNAME=your_user
# DB_PASSWORD=your_password

php artisan migrate
php artisan db:seed
```

## Configuration

### GeoIP (Optional)

Download MaxMind GeoLite2-City database:
1. Register at https://www.maxmind.com/en/geolite2/signup
2. Download GeoLite2-City.mmdb
3. Place in `storage/geoip/GeoLite2-City.mmdb`

### Queue Worker

For background job processing:
```bash
php artisan queue:work
```

### Scheduled Tasks

Add to crontab:
```bash
* * * * * cd /path/to/backend && php artisan schedule:run >> /dev/null 2>&1
```

## API Endpoints

### SDK Ingestion (API Key Auth)
- `POST /api/v1/ingest` - Bulk event ingestion
- `POST /api/v1/crash` - Crash report
- `POST /api/v1/replay` - Session replay frames

### Dashboard API (Sanctum Auth)
- `GET/POST /api/projects` - Project management
- `GET /api/projects/{id}/overview` - Analytics dashboard
- `GET /api/projects/{id}/events` - Event explorer
- `GET /api/projects/{id}/crashes` - Crash reports
- `GET /api/projects/{id}/replays` - Session replays

## License

MIT
# AppSignals
