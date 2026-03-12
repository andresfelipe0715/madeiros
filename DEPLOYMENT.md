# Deployment and Docker Guide

This guide provides the necessary commands to manage the application's Docker containers and instructions for setting up a VPS from scratch.

## Docker Commands Reference

### Starting Containers
```bash
# Start all containers in the background
docker compose up -d

# Start all containers and see the output (hot-reload for logs)
docker compose up
```

### Stopping Containers
```bash
# Stop and remove containers
docker compose down

# Stop containers without removing them
docker compose stop
```

### Running Commands Inside Docker
Since the application runs inside containers, you need to use `docker compose exec` to run Artisan or Composer commands.

```bash
# Run Artisan commands
docker compose exec app php artisan migrate
docker compose exec app php artisan db:seed --class=ProductionDataSeeder

# Run Composer commands
docker compose exec app composer install

# Open a shell inside the app container
docker compose exec app bash
```

---

## VPS Setup Guide (Ubuntu/Debian)

### 1. Initial Server Setup
Connect to your VPS:
```bash
ssh root@your_vps_ip
```

Update system packages:
```bash
sudo apt update && sudo apt upgrade -y
```

### 2. Install Docker & Docker Compose
Install Docker:
```bash
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh
```

Install Docker Compose:
```bash
sudo apt install docker-compose-plugin -y
```

### 3. Clone the Project
```bash
git clone https://github.com/your-username/madeiros.git
cd madeiros
```

### 4. Configuration
Create the `.env` file from the example:
```bash
cp .env.example .env
```
Edit the `.env` file to set your production values (DB_PASSWORD, APP_URL, etc.):
```bash
nano .env
```

### 5. Launch the Application
Build and start the containers:
```bash
docker compose up -d --build
```
> [!NOTE]
> On the first run, Docker will automatically create the `docker/mysql` directory on your VPS. MySQL will then take a few seconds to initialize these files before it becomes ready to accept connections.

### 6. Post-Launch Setup (First Time Only)
Install dependencies and run migrations:
```bash
docker compose exec app composer install --no-dev --optimize-autoloader
docker compose exec app php artisan key:generate

# 1. Create tables
docker compose exec app php artisan migrate --force

# 2. Populate essential data (Roles, Stages and Admin user)
docker compose exec app php artisan db:seed --class=ProductionDataSeeder --force

# 3. Setup storage and permissions
docker compose exec app php artisan storage:link
docker compose exec app chmod -R 775 storage bootstrap/cache
```

---

## Data Management

### 1. Persistence
Your database data is stored locally in the `docker/mysql` directory. This directory is ignored by Git to avoid conflicts between different environments. When you backup this folder, you are backing up your raw database files.

### 2. Loading Existing Data
If you have a SQL dump (e.g., `backup.sql`) from another installation, you have two ways to load it:

#### Option A: Using phpMyAdmin (Recommended for GUI)
1. Start the GUI: `docker compose -f docker-compose.gui.yml up -d`
2. Open `http://your-vps-ip:8080`.
3. Select your database and use the **Import** tab to upload your `.sql` file.

#### Option B: Using the Command Line
Place your `backup.sql` in the project root and run:
```bash
# Import the SQL file into the database container
docker exec -i madeiros-db mysql -u root -p'YOUR_PASSWORD' madeiros < backup.sql
```

---

---

## Maintenance

### Updating the Application
```bash
git pull origin main
docker compose up -d --build
docker compose exec app php artisan migrate --force
```

### Checking Logs
```bash
docker compose logs -f
```

---

## On-Demand Maintenance GUI

The database GUI is kept separate from the core production services for security and performance. Only start it when you explicitly need to perform manual database operations.

### 1. Launch phpMyAdmin
```bash
docker compose -f docker-compose.gui.yml up -d
```
Access via: `http://your-vps-ip:8080`

### 2. Stop phpMyAdmin
```bash
docker compose -f docker-compose.gui.yml stop
```

---

## Troubleshooting

### Docker Build: "image already exists"
If you encounter an error like `failed to solve: image "madeiros-app:latest": already exists` during a build, it is likely a BuildKit cache synchronization issue.

**Solution:**
1. Clear the builder cache:
   ```bash
   docker builder prune -f
   ```
2. Rebuild the main app service without cache:
   ```bash
   docker compose build --no-cache app
   ```
3. Start the containers normally:
   ```bash
   docker compose up -d
   ```

### Frontend not styling or "Vite manifest" error
If the application appears unstyled or you see an error about a missing Vite manifest, it usually means the frontend assets aren't compiled or the app is stuck in "hot reload" mode.

**Solution:**
1. Ensure the `public/hot` file is deleted (this file forces the app to look for a dev server):
   ```bash
   # Windows
   del public\hot
   # Linux/Mac
   rm public/hot
   ```
2. Compile the assets on your host machine:
   ```bash
   npm run build
   ```
3. Refresh the browser. Since the project root is mapped as a volume to the `app` container, the new `public/build` files will be available immediately.


