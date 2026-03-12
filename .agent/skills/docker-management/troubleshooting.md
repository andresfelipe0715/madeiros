# Docker Troubleshooting Guide

Common issues and their solutions for the Docker environment.

## 1. Port Conflict (80 or 3306)
**Symptom:** `Error starting userland proxy: listen tcp 0.0.0.0:80: bind: address already in use`.

**Solution:**
- Find and stop the service using the port.
- Or, change the local port in `docker-compose.yml`:
  ```yaml
  nginx:
    ports:
      - "8080:80" # Change 80 to 8080
  ```

## 2. Permission Denied (Storage/Cache)
**Symptom:** `The stream or file "/var/www/storage/logs/laravel.log" could not be opened: failed to open stream: Permission denied`.

**Solution:**
- Fix permissions inside the container:
  ```bash
  docker-compose exec app chown -R laravel:www-data storage bootstrap/cache
  docker-compose exec app chmod -R 775 storage bootstrap/cache
  ```

## 3. Database Connection Refused
**Symptom:** `SQLSTATE[HY000] [2002] Connection refused` when running migrations.

**Solution:**
- Ensure the `db` service is healthy: `docker-compose ps`.
- Check logs: `docker-compose logs db`.
- verify `.env` uses `DB_HOST=db` (the service name in `docker-compose.yml`).

## 4. "No such file or directory" for .env
**Symptom:** Containers fail to start because environment variables are missing.

**Solution:**
- Ensure `.env` exists in the root directory where `docker-compose.yml` is located.
- Rebuild if you just created it: `docker-compose up -d`.

## 5. BuildKit Cache Issues
**Symptom:** `failed to solve: image "madeiros-app:latest": already exists` during build.

**Solution:**
1. Clear the builder cache: `docker builder prune -f`
2. Rebuild the app service: `docker-compose build --no-cache app`

## 6. Frontend/Vite Issues
**Symptom:** Unstyled application or "Vite manifest" error.

**Solution:**
1. Remove the hot reload file: `rm public/hot` (Linux) or `del public\hot` (Windows).
2. Compile assets on host: `npm run build`.

## Real-World Impact
Quickly resolving these common issues keeps the development flow smooth and reduces frustration when setting up or modifying the environment.
