# Docker Setup Guide: From Scratch

Follow these steps to set up the environment on a new machine.

## Prerequisites
- Docker Desktop (Windows/Mac) or Docker Engine (Linux) installed and running.
- PHP and Composer installed locally (optional, but recommended for initial setup).

## Steps

1. **Clone the Repository**
   ```bash
   git clone [repo-url] madeiros
   cd madeiros
   ```

2. **Configure Environment**
   - Copy `.env.example` to `.env`.
   - Update `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` to match `docker-compose.yml`.
   - Set `APP_DOMAIN=localhost`.

3. **Start Containers**
   ```bash
   docker-compose up -d --build
   ```

4. **Install Dependencies**
   ```bash
   docker-compose exec app composer install
   ```

5. **Generate Application Key**
   ```bash
   docker-compose exec app php artisan key:generate
   ```

6. **Run Migrations and Seeders**
   ```bash
   # Run migrations
   docker-compose exec app php artisan migrate --force

   # Seed production data
   docker-compose exec app php artisan db:seed --class=ProductionDataSeeder --force
   ```

7. **Verify Installation**
   - Visit `http://localhost` in your browser.
   - Run `docker-compose ps` to ensure all services are healthy.

## Real-World Impact
Following this guide ensures a consistent development environment across different machines, preventing "it works on my machine" issues.
