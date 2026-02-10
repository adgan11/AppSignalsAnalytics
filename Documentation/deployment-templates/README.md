# AppSignals Deployment Templates

This folder contains ready-to-copy production templates for common services.

## Included

- `nginx.conf` - Nginx server block
- `apache-vhost.conf` - Apache VirtualHost
- `supervisor-queue.conf` - queue worker
- `supervisor-reverb.conf` - Reverb server
- `cron.txt` - Laravel scheduler
- `docker/` - optional Docker and Docker Compose templates

## Usage

1) Replace `analytics.example.com` and the `/var/www/appsignals/backend` paths to match your server.
2) For Supervisor, place the files in `/etc/supervisor/conf.d/` and reload Supervisor.
3) Add the cron line from `cron.txt` to your crontab.
4) For Docker, follow `docker/README.md`.
