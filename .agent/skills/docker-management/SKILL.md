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

## Quick Reference

| Phase | Key Actions | Success Criteria |
|-------|-------------|------------------|
| **1. Checks** | Validate `.env`, ports | Environment is ready |
| **2. Lifecycle**| `up -d --build`, `ps` | All services are "Up" |
| **3. App Init** | `composer install`, `migrate` | App is functional |
