---
name: deployment
description: "Use when asked to set up a new server/VPS from scratch or handle deployment tasks like initial provisioning and data restoration."
risk: high
source: project
date_added: "2026-03-12"
---

# Deploying to a VPS

## Overview

A new VPS setup is a delicate process where missteps in Docker installation or initial container bootstrapping can cause hard-to-debug permission issues or data loss. This skill provides an opinionated playbook for ensuring a reproducible deployment on Ubuntu/Debian.

**Core principle:** Run provisioning commands sequentially and verify each step before proceeding.

## The Iron Law

```
NEVER IMPORT A DATABASE SQL DUMP BEFORE ENSURING ALL CONTAINERS ARE HEALTHY.
```

If the database container hasn't fully initialized, trying to import data or run migrations will corrupt the volume.

## When to Use

Use for:
- Provisioning a brand-new Ubuntu/Debian VPS.
- Cloning the project onto a newly set up server.
- The very first sequence of `docker compose up` on a remote machine.
- Importing an existing `.sql` database dump.

Do NOT use when:
- Performing routine day-to-day Docker management locally (use `docker-management` instead).
- Fixing local database corruption on an existing environment.

## The Phases

You MUST complete each phase before proceeding.

### Phase 1: Server Provisioning

**Before you clone the repository:**

1. **System Setup**
   - Connect to the VPS: `ssh root@your_vps_ip`
   - Update core packages: `sudo apt update && sudo apt upgrade -y`

2. **Docker Installation**
   - Install the Docker Engine using the official script: `curl -fsSL https://get.docker.com -o get-docker.sh && sudo sh get-docker.sh`
   - Install Docker Compose: `sudo apt install docker-compose-plugin -y`

### Phase 2: Application Bootstrapping

1. **Clone and Configure**
   - Clone the repo: `git clone https://github.com/your-username/madeiros.git && cd madeiros`
   - Set up `.env`: `cp .env.example .env`.
   - Edit the `.env` file to set production credentials (e.g., `DB_PASSWORD`, `APP_URL`).

2. **First Container Launch**
   - Run the initial build and start: `docker compose up -d --build`
   - **Critical check:** Wait 30 seconds for MySQL to initialize its local `docker/mysql` directory. Monitor `docker compose logs -f db` until it displays "ready for connections".

3. **Production Initialization**
   - Install dependencies: `docker compose exec app composer install --no-dev --optimize-autoloader`
   - Generate key: `docker compose exec app php artisan key:generate`
   - Run migrations: `docker compose exec app php artisan migrate --force`
   - Seed essential data: `docker compose exec app php artisan db:seed --class=ProductionDataSeeder --force`
   - Fix permissions and storage: `docker compose exec app php artisan storage:link && docker compose exec app chmod -R 775 storage bootstrap/cache`

### Phase 3: Data Restoration (If Applicable)

If there is an existing `.sql` data dump to restore on the new server.

1. **Option A: CLI Restore (Fast)**
   - Assuming `backup.sql` is in the project root.
   - Run: `docker exec -i madeiros-db mysql -u root -p'YOUR_PASSWORD' madeiros < backup.sql`

2. **Option B: GUI Restore**
   - Start the admin UI: `docker compose -f docker-compose.gui.yml up -d`
   - Connect to `http://your-vps-ip:8080` (use `root` and the `.env` DB password)
   - Import the file manually through phpMyAdmin UI.
   - Stop the GUI: `docker compose -f docker-compose.gui.yml stop`

## Red Flags — STOP and Restart

If you are thinking:
- "I'll just install Docker from the default apt repos instead of the install script."
- "The database container is up, I can instantly start importing data."
- "I don't really need to set up the .env file before running docker compose."

**ALL of these mean: STOP. Return to Phase 1.**

## Quick Reference

| Phase | Key Actions | Success Criteria |
|-------|-------------|------------------|
| **1. Provision** | Update APT, `get-docker.sh` | Docker commands available |
| **2. Bootstrap** | `clone`, `copy .env`, `up --build` | Containers running, initialized |
| **3. Restore** | `docker exec -i ... < backup.sql` | Legacy data accessible |
