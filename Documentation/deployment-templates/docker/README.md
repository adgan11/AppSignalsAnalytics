# Docker Templates (Optional)

These files provide a simple, production-style Docker setup for AppSignals.

## Quick start (from the repo root)

1) Generate an app key and update the compose file:

```
php -r "echo 'base64:'.base64_encode(random_bytes(32))."\n";
```

2) Edit `Documentation/deployment-templates/docker/docker-compose.yml` and set:
- `APP_KEY`
- `APP_URL`
- `VITE_REVERB_HOST`

3) Build and start the stack:

```
docker compose -f Documentation/deployment-templates/docker/docker-compose.yml up -d --build
```

4) Run migrations:

```
docker compose -f Documentation/deployment-templates/docker/docker-compose.yml exec app php artisan migrate --force
```

## Notes

- The compose file starts `app`, `queue`, `reverb`, and `scheduler` containers.
- Update database credentials and ports if 3306 is already in use.
- For HTTPS in production, place a reverse proxy (Nginx/Caddy) in front of the app.
