---
name: docker-management
description: "Use when asked to manage Docker containers or start the environment from scratch."
risk: medium
source: project
date_added: "2026-03-12"
---

## Overview

Managing Docker in a Laravel environment can lead to permission issues, port conflicts, and database connection errors if not handled correctly. This skill provides a structured playbook for the entire lifecycle of Docker containers in this project.

**Core principle:** Always verify container health and environment configuration before executing application commands.

## The Iron Law

```
NEVER RUN 'docker-compose up' WITHOUT VERIFYING THE .env FILE FIRST.
```

Missing or incorrect environment variables are the leading cause of container failure.

## When to Use

Use for:
- Starting the development environment from scratch
- Stopping, restarting, or rebuilding containers
- Accessing container shells (bash/sh)
- Troubleshooting database or networking issues

ESPECIALLY use when:
- Deploying to a new environment (VPS, WSL2)
- Seeing "Connection Refused" or "Permission Denied" errors

Do NOT use when:
- Running standard Artisan commands that don't require container context (unless running them *inside* the container)

## The Phases

You MUST complete each phase before proceeding.

### Phase 1: Pre-flight Checks

**Before you do anything:**

1. **Environment Validation**
   - Verify `.env` exists and contains `DB_DATABASE`, `DB_PASSWORD`, and `APP_DOMAIN`.
   - Ensure `docker-compose.yml` is present in the root.

2. **Resource Check**
   - Verify Docker Desktop (or engine) is running.
   - Check if ports 80 (Nginx) and 3306 (MySQL) are already in use by local services.

### Phase 2: Lifecycle Management

1. **Starting the Engine**
   - Run `docker-compose up -d --build` to ensure the latest images are used.
   - Run `docker-compose ps` to verify all services (`app`, `db`, `nginx`, `cron`) are "Up".

2. **Health Verification**
   - Use `docker-compose logs -f db` to ensure MySQL initialized correctly.
   - Verify `db` health status via `docker inspect`.

### Phase 3: Application Initialization

1. **Dependencies & Permissions**
   - Run `docker-compose exec app composer install`.
   - For production, use: `composer install --no-dev --optimize-autoloader`.
   - Ensure storage and cache directories have correct permissions: `docker-compose exec app chmod -R 775 storage bootstrap/cache`.

2. **Database Setup**
   - Run `docker-compose exec app php artisan migrate --force`.
   - For essential data (Roles, Stages, Admin), run: `docker-compose exec app php artisan db:seed --class=ProductionDataSeeder --force`.
   - To access the Maintenance GUI (phpMyAdmin), use: `docker-compose -f docker-compose.gui.yml up -d`.

## Red Flags — STOP and Restart

If you are thinking:
- "I'll just ignore the database error and fix it later."
- "The container is 'Exit 1' but I can still run artisan commands locally."
- "I don't need to rebuild the image after changing the Dockerfile."

**ALL of these mean: STOP. Return to Phase 1.**

## Phase 4: Troubleshooting

If you encounter issues during Phase 2 or Phase 3, follow these steps before retrying.

### **Database Corruption or Crash Loop**

If `madeiros-db` container repeatedly exits or logs show `Table 'mysql.plugin' doesn't exist` or privilege table errors, the data directory is likely corrupted.

1. **Verify**: Run `docker compose logs --tail=50 db` to inspect the error.
2. **Stop & Remove Container**: `docker compose stop db`, then `docker compose rm -f db`.
3. **Wipe Local Data**: Delete the contents of the local `docker/mysql` directory (e.g., `Remove-Item -Recurse -Force .\docker\mysql\*` on Windows or `rm -rf docker/mysql/*` on Linux/macOS). **Warning: This destroys all local database data.**
4. **Restart**: `docker compose up -d db`. Observe the logs to ensure a clean initialization. Wait for `mysqld: ready for connections`.

### **DNS or Build Error (Image Already Exists)**

If `docker compose build` fails with `failed to solve: image "madeiros-app:latest": already exists` or random DNS lookup failures (e.g., `lookup registry-1.docker.io: no such host`):

1. **Verify Internet/DNS**: Ensure your host machine can reach the internet (e.g., `ping docker.io`).
2. **Clear BuildKit Cache**: Run `docker builder prune -f` to clear caching issues.
3. **Rebuild**: `docker compose build --no-cache app`.

### **GUI (phpMyAdmin) Empty or Showing "Ghost" Data**

If phpMyAdmin logs in successfully but shows "No tables found" while terminal commands (`show tables`) or the app work correctly:

1. **Verify Connectivity**: Run `docker exec madeiros-phpmyadmin getent hosts madeiros-db` to ensure it resolves to the correct internal IP.
2. **Explicit Hostname**: Ensure `PMA_HOST` in `docker-compose.gui.yml` is set to the explicit container name `madeiros-db` instead of the service alias `db`.
3. **Port Collision**: If using port `8080` on Windows, another service (or a hidden ghost container from a different project) might be hijacking the traffic. 
   - **Fix**: Change the host port in `docker-compose.gui.yml` to something unique like `8099:80`.
4. **Restart**: `docker compose -f docker-compose.gui.yml up -d --force-recreate`.

## Quick Reference

| Phase | Key Actions | Success Criteria |
|-------|-------------|------------------|
| **1. Checks** | Validate `.env`, ports | Environment is ready |
| **2. Lifecycle**| `up -d --build`, `ps` | All services are "Up" |
| **3. App Init** | `composer install`, `migrate` | App is functional |
| **4. Troubleshoot** | Wipe `/docker/mysql`, use port 8099 | DB logs clean, GUI shows 25+ tables |
