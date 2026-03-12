# Anti-Patterns: Laravel Storage Management

## Overview

Identifying what NOT to do is as important as knowing the correct steps. These anti-patterns represent the most common ways agents fail to resolve storage issues in containerized environments.

## The "Chmod 777" Reflex

**The symptom:** Running `chmod -R 777 storage` as the first response to a 403 error.
**Why it fails:** 
- In 90% of containerized Laravel cases, the 403 is caused by Nginx trying to follow a broken symlink or a symlink that points to a path that doesn't exist *inside* the Nginx container.
- It creates a security vulnerability without solving the root configuration issue.
- Even with 777 permissions, if the symlink is broken, Nginx will still return 403 or 404.

## The "Host-Side Fix"

**The symptom:** Running `php artisan storage:link` on the host machine (Windows/WSL) instead of inside the container.
**Why it fails:**
- Laravel generates the symlink using absolute paths.
- If run on the host, the link might point to `C:\Users\...\storage\app\public`.
- Inside the Docker container, that path does not exist. The container needs the link to point to `/var/www/storage/app/public`.

## Ignoring the URL Structure

**The symptom:** Trying to fix file permissions when the browser is requesting `http://localhost/storage/0`.
**Why it fails:**
- If the URL is `storage/0`, the issue is in the **Database** or the **Model Attribute Casting**.
- No amount of permission or symlink fixing will make a file named "0" appear.
- You must trace why the `file_path` column contains "0".

## Success Checklist

Before you say "it's fixed":
- [ ] Can you `curl -I` the file and get a `200 OK`?
- [ ] Does the `APP_URL` in `.env` match the domain/port you are using in the browser?
- [ ] Did you run `storage:link --force` *inside* the app container?
- [ ] Did you verify the database record contains a valid string path?
