# Docker & Laravel Management Cheat Sheet

This document summarizes the key management commands, recent environment fixes, and safety rules for the Madeiros Docker environment.

## 🚀 The workflow
To start the project from scratch or apply major configuration changes:
1. **Build & Start**: `docker compose up -d --build`
2. **Initialize**:
   - `docker compose exec app composer install`
   - `docker compose exec app php artisan migrate --force`
   - `docker compose exec app php artisan storage:link`

---

## 🛠️ Essential Commands

| Action | Command |
| :--- | :--- |
| **Start Services** | `docker compose up -d` |
| **Stop Services** | `docker compose stop` |
| **Full Restart (DNS Refresh)** | `docker compose restart` |
| **Complete Shutdown** | `docker compose down` |
| **Reset DB & Production Seed** | `docker compose exec app php artisan migrate:fresh --seed --seeder=ProductionDataSeeder --force` |
| **Start Database GUI** | `docker compose -f docker-compose.gui.yml up -d` (Access: `localhost:8099`) |

---

## ✅ Recent Environment Fixes

### 1. Image Uploads (500 Error)
- **Problem**: `imagejpeg()` was undefined.
- **Fix**: Recompiled PHP GD extension with `libjpeg`, `libfreetype`, and `libwebp` support in the `Dockerfile`.

### 2. Large File Uploads (413 Error)
- **Problem**: Default 2MB limit was too small for staging evidence.
- **Fix**: Increased limit to **64MB** in both Nginx (`app.conf.template`) and PHP (`Dockerfile`).

### 3. Nginx Connectivity (502 Error)
- **Problem**: Nginx cached stale internal IP addresses after app rebuilds.
- **Fix**: Use `docker compose restart` to clear the internal DNS cache.

### 4. Database Visibility (phpMyAdmin)
- **Problem**: Empty database view due to service alias collisions and port 8080 conflicts.
- **Fix**: Updated `PMA_HOST` to `madeiros-db` and shifted the host port to **8099**.

---

## ⚠️ Safety Rules
- **Data Persistence**: Your database records are safe in `docker/mysql` and your code is safe in the project root. `docker compose down` will **not** delete your data.
- **Manual Edits**: If you edit the `Dockerfile` or Nginx templates, you **must** run `docker compose up -d --build` to apply the changes.
- **Wiping Data**: If you need to start the database from a total blank state, delete the contents of the `docker/mysql` folder and restart the `db` container.
