# Call Center Management System

A comprehensive call center management system built with Symfony, following Domain-Driven Design principles. The system handles employee scheduling, skill management, call tracking, and various types of leave requests.

## Features

- Employee management with skill tracking
- Call history and statistics
- Work schedule management
- Leave request system (Holiday, Sick, Personal, Maternity, Paternity)
- Contract type handling (B2B, Civil, Employment)
- Polish holiday calendar integration

## Requirements

- PHP 8.2 or higher
- MySQL 8.0
- Docker and Docker Compose
- Composer

## Installation

1. Clone the repository:
```bash
git clone git@github.com:kamil220/Call-center-schedule.git
cd Call-center-schedule
```

2. Start the Docker containers:
```bash
docker compose up -d
```

3. Install dependencies:
```bash
docker compose exec app composer install
```

4. Create and set up the database:
```bash
docker compose exec app bin/console doctrine:database:create
docker compose exec app bin/console doctrine:migrations:migrate
```

5. Load test data (optional but recommended for demo purpose):
```bash
docker compose exec app bin/console doctrine:fixtures:load
```

## Project Structure

The project follows a Domain-Driven Design architecture with the following structure:

```
src/
├── Domain/              # Core business logic and domain models
├── Application/         # Use cases, commands, queries
├── Infrastructure/      # Technical implementations
└── UI/                 # User interfaces (API controllers)
```

### Key Domains

- **Employee**: Manages employee data, skills, and roles
- **Schedule**: Handles work schedules and shift assignments
- **Call**: Tracks call history and statistics
- **WorkSchedule**: Manages availability and leave requests

## API Documentation

The API endpoints are organized by domain and follow REST principles.

Detailed API documentation is available through Swagger UI at:
```
http://localhost:8080/api/doc
```
You can access this interactive documentation after starting the Docker containers. It provides detailed information about all endpoints, request/response schemas, and allows testing the API directly from the browser.

## Development

### Running Tests

```bash
docker compose exec app bin/phpunit
```

### Code Style

The project follows PSR-12 coding standards. To check the code style:

```bash
docker compose exec app vendor/bin/phpcs
```

### Memory Configuration

The application is configured with a 2GB memory limit for PHP processes. 
This high limit is specifically set to handle the generation of large amounts of demo data through fixtures.
For production environments, it is recommended to reduce this limit to 256MB-512MB, which can be adjusted in the Dockerfile.

## Helpfull commands

### Restore demo data
docker-compose exec -it app php bin/console doctrine:database:drop --force && 
docker-compose exec -it app php bin/console doctrine:database:create && 
docker-compose exec -it app php bin/console doctrine:migrations:migrate --no-interaction && 
docker-compose exec -it app php bin/console doctrine:fixtures:load --append