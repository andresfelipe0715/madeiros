# VPS Deployment Guide for Madeiros

This guide provides a step-by-step process to set up your repository on a fresh Ubuntu VPS. We use Docker and Docker Compose for a consistent production environment.

## User Review Required

> [!IMPORTANT]
> - Ensure you have **root** or **sudo** access on the VPS.
> - You must manually configure the `.env` file on the VPS.
> - Use `sudo` for all Docker commands if your user is not in the `docker` group.

## Proposed Setup Steps

### Phase 1: Server Provisioning
Run these from any location (e.g., `~/`):

1. **Update System**
   ```bash
   sudo apt update && sudo apt upgrade -y
   ```

2. **Install Docker & Compose**
   ```bash
   curl -fsSL https://get.docker.com -o get-docker.sh && sudo sh get-docker.sh
   sudo apt install docker-compose-plugin -y
   ```

### Phase 1.5: Firewall Configuration
Allow traffic on essential ports:
```bash
sudo ufw allow 22/tcp     # SSH (Crucial)
sudo ufw allow 80/tcp     # HTTP (Web)
sudo ufw allow 443/tcp    # HTTPS (SSL)
sudo ufw allow 81/tcp     # Proxy Manager Admin
sudo ufw allow 8888/tcp   # Direct App Access (Nginx)
sudo ufw enable
```
> [!NOTE]
> Also open these ports in your **Hostinger Cloud Dashboard** firewall settings.

---

### Phase 1.8: Central Proxy Setup
This must be done first so the system-wide network (`proxy-tier`) is created.

1. **Create the Folder**
   ```bash
   mkdir -p ~/docker/proxy
   cd ~/docker/proxy
   ```

2. **Start the Proxy**
   *Create the `docker-compose.yml` file in this folder.*
   ```bash
   docker compose up -d
   ```

---

### Phase 2: Application Bootstrapping
Navigate to your project folder (`cd ~/madeiros`):

1. **Pull Latest Code**
   ```bash
   git pull origin dev
   ```

2. **Configure Environment (`.env`)**
   ```bash
   cp .env.example .env
   nano .env
   ```
   *Set `APP_ENV=production`, `APP_DEBUG=false`, `DB_PASSWORD`, and `APP_URL`.*

3. **Build & Start Containers**
   ```bash
   sudo docker compose up -d --build
   ```
   > [!TIP]
   > If you see a **502 Bad Gateway** or a **404 File Not Found** after rebuilding, try a "Hard Restart":
   > `sudo docker compose down && sudo docker compose up -d`

---

### Phase 3: Laravel & Frontend Initialization
Run these inside the context of the running containers:

1. **PHP Dependencies & Key**
   ```bash
   sudo docker compose exec app composer install --no-dev --optimize-autoloader
   sudo docker compose exec app php artisan key:generate
   ```

2. **Database Setup**
   ```bash
   sudo docker compose exec app php artisan migrate --force
   sudo docker compose exec app php artisan db:seed --class=ProductionDataSeeder --force
   ```

3. **Frontend Asset Build**
   ```bash
   sudo docker compose exec app npm install
   sudo docker compose exec app npm run build
   ```

4. **Storage & Permissions**
   ```bash
   sudo docker compose exec app php artisan storage:link
   sudo docker compose exec app chmod -R 775 storage bootstrap/cache
   ```

## Troubleshooting 500 Errors
If the app shows a 500 error, check the Laravel log file:
```bash
sudo docker compose exec app tail -n 50 storage/logs/laravel.log
```

## Verification Plan
1. **Direct Access**: Visit `http://your-vps-ip:8888`
2. **Proxy Access**: Visit `http://your-vps-ip:81` for the Nginx Proxy Manager setup.
