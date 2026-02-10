# Server Requirements (One-Page Summary)

This page lists the minimum requirements to run AppSignals in production.

## Minimum (Small Installations)
- 2 vCPU
- 2 GB RAM
- 10 GB SSD storage

## Recommended (Growing Projects)
- 4 vCPU
- 4–8 GB RAM
- 20+ GB SSD storage

## Software
- PHP 8.2+
- Composer 2.x
- MySQL 8.0+ or MariaDB 10.6+
- Node.js 18+ (only needed to build frontend assets)
- Nginx or Apache with PHP-FPM

## Required PHP Extensions
- bcmath
- ctype
- fileinfo
- json
- mbstring
- openssl
- pdo
- tokenizer
- xml

## Services and Jobs
- A queue worker process (`php artisan queue:work`)
- A scheduler cron entry (`php artisan schedule:run` every minute)
- Reverb realtime server (`php artisan reverb:start`)

## Network Ports
- 80/443 for the web app
- 8080 for Reverb (or 443 if proxied through HTTPS)

## Filesystem Permissions
- `backend/storage` and `backend/bootstrap/cache` must be writable by the web server user.
