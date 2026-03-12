# Docker Essential Commands

Use these commands to manage the Docker environment for this project.

## Lifecycle Management

### Start the environment
```bash
docker-compose up -d --build
```

### Stop containers without removing them
```bash
docker-compose stop
```

### Restart a specific service
```bash
docker-compose restart app
```

### On-Demand Maintenance GUI (phpMyAdmin)
```bash
# Start phpMyAdmin
docker-compose -f docker-compose.gui.yml up -d

# Stop phpMyAdmin
docker-compose -f docker-compose.gui.yml stop
```

### View real-time logs
```bash
docker-compose logs -f
```

## Shell Access

### Access the PHP container (as laravel user)
```bash
docker-compose exec app bash
```

### Access the Database container
```bash
docker-compose exec db mysql -u root -p
```

## Application Commands

### Run Composer commands
```bash
docker-compose exec app composer [command]
```

### Run Artisan commands
```bash
docker-compose exec app php artisan [command]
```

### Run Pest tests
```bash
docker-compose exec app php artisan test
```

## Maintenance

### Prune unused Docker objects
```bash
docker system prune -f
```

### Force rebuild without cache
```bash
docker-compose build --no-cache
```
