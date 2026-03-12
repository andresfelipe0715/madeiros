# Storage Troubleshooting Checklist (WSL/Docker)

Use this quick checklist to diagnose 403/Broken Storage links in seconds.

## 1. Environment Check
- [ ] `APP_URL` in `.env` matches the browser address (e.g., `http://localhost`).
- [ ] `FILESYSTEM_DISK` is set to `public`.
- [ ] Nginx `server_name` matches `APP_URL` domain.

## 2. Symlink Verification (Inside Container)
- [ ] Run `docker compose exec app ls -la public/storage`.
- [ ] Does it point to `/var/www/storage/app/public`?
- [ ] If no, run `docker compose exec app php artisan storage:link --force`.

## 3. Data Integrity
- [ ] Check DB record: `App\Models\OrderFile::find($id)->file_path`.
- [ ] Is it a string like `orders/file.pdf`?
- [ ] If it's `0`, the issue is in the Controller/Request upload logic.

## 4. Final Pulse Check
- [ ] Run `curl -I http://localhost/storage/path/to/file.pdf`.
- [ ] Result is `200 OK`.
- [ ] If `403`, check Nginx error logs: `docker logs madeiros-nginx`.
