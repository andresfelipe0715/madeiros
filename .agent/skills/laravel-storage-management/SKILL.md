---
name: laravel-storage-management
description: "Use when users report 403 Forbidden errors or broken links for storage files (PDFs, images) in containerized environments."
risk: low
source: project
date_added: "2026-03-12"
---

# Laravel Storage Management

## Overview

Storage files failing to load (403 Forbidden or 404) often result from a mismatch between the container's internal filesystem, the Nginx root, the `APP_URL`, and the generated symlink. Without this skill, agents waste time on file permissions when the issue is actually the symlink's absolute path or the `.env` configuration.

**Core principle:** A broken storage link is a configuration mismatch until proven otherwise.

## The Iron Law

```
NEVER RECREATE THE SYMLINK BEFORE VERIFYING THE APP_URL.
```

Recreating a symlink while the `APP_URL` is wrong will only mask the issue or lead to inconsistent URL generation in the UI.

## When to Use

Use for:
- 403 Forbidden on `/storage/*` paths.
- 404 Not Found on files that clearly exist in `storage/app/public`.
- PDFs or images showing broken icons in the browser.
- "storage/0" or other obviously incorrect paths appearing in generated URLs.

Do NOT use when:
- Resolving general 500 errors.
- Fixing database connection issues.

## The Phases

You MUST complete each phase before proceeding.

### Phase 1: Environment & URL Verification

1. **Check .env configuration**
   - Run `grep "APP_URL" .env` and `grep "FILESYSTEM_DISK" .env`.
   - Ensure `APP_URL` matches the port Nginx is listening on (e.g., `http://localhost` if on port 80).
   - Verify `FILESYSTEM_DISK` is set to `public` if that's the intention.

2. **Verify Nginx Server Name**
   - Check `docker-compose.yml` environment variables for Nginx (e.g., `APP_DOMAIN`).
   - Ensure Laravel's `APP_URL` and Nginx's `server_name` are in sync.

### Phase 2: Symlink Resync

1. **Force Recreate Link**
   - Run `docker compose exec app php artisan storage:link --force`.
   - **Crucial:** Always run this *inside* the container to ensure the symlink points to the container's absolute path (`/var/www/storage/app/public`), not the host's path.

2. **Clear Cache**
   - Run `docker compose exec app php artisan config:clear`.
   - This ensures the new `APP_URL` is picked up by the URL generator.

### Phase 3: Data & Permission Validation

1. **Verify Database Integrity**
   - Use `tinker` to check the stored paths: `App\Models\OrderFile::latest()->first()->file_path`.
   - Look for "0" or absolute host paths that shouldn't be there.
   - If found, manually update records to the relative path (`orders/filename.pdf`).

2. **Check Container Permissions**
   - Run `docker exec <container> ls -la public/storage`.
   - The link should point to `/var/www/storage/app/public`.
   - The owner should typically be `www-data` or the user Nginx/PHP is running as.

## Red Flags — STOP and Restart

If you are thinking:
- "I'll just chmod 777 everything." (Security risk, masks the real config issue).
- "The symlink looks fine on my host." (Irrelevant; it must be fine *inside* the container).
- "I'll ignore the 'storage/0' in the URL and fix permissions first." (The path is fundamentally wrong; permissions won't fix it).

**ALL of these mean: STOP. Return to Phase 1.**

## Quick Reference

| Phase | Key Actions | Success Criteria |
|-------|-------------|------------------|
| **1. Env Check** | Verify `APP_URL` & `APP_DOMAIN` | `APP_URL` matches browser access point |
| **2. Link Resync** | `storage:link --force` (Inside Container) | Symlink points to `/var/www/storage/...` |
| **3. Data Check** | Verify DB path strings | No "0" or host-specific paths in DB |
