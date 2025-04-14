# Running the Project with Docker

## Requirements
- Docker
- Docker Compose

## Setup Steps

1. Clone the project repository
   ```
   git clone [repository-address]
   cd [directory-name]
   ```

2. Copy the Docker environment configuration file to .env.local
   ```
   cp .env.docker .env.local
   ```

3. Build and run containers
   ```
   docker-compose up -d --build
   ```

4. Install Composer dependencies (if not installed during build)
   ```
   docker-compose exec app composer install
   ```

5. Clear cache
   ```
   docker-compose exec app bin/console cache:clear
   ```

6. Create database (if it doesn't exist)
   ```
   docker-compose exec app bin/console doctrine:database:create --if-not-exists
   ```

7. Run migrations
   ```
   docker-compose exec app bin/console doctrine:migrations:migrate --no-interaction
   ```

8. (Optional) Load test data
   ```
   docker-compose exec app bin/console doctrine:fixtures:load --no-interaction
   ```

## Application Access

- Symfony Application: http://localhost:8080
- MySQL Database:
  - Host: localhost
  - Port: 3306
  - User: app_user
  - Password: app_pass
  - Database: app_db
- Redis: localhost:6379

## Stopping Containers

```
docker-compose down
```

## Stopping Containers and Removing Volumes

```
docker-compose down -v
``` 