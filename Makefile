.PHONY: help
help: ## Show this help
	@echo "Symfony Project Makefile"
	@echo "----------------------------------"
	@echo "Usage: make [target]"
	@echo ""
	@echo "Targets:"
	@grep -E '(^[a-zA-Z0-9_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}{printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'

##
## Code Quality
##

.PHONY: cs
cs: ## Run PHP-CS-Fixer
	vendor/bin/php-cs-fixer fix --diff --verbose

.PHONY: cs-dry
cs-dry: ## Run PHP-CS-Fixer in dry-run mode
	vendor/bin/php-cs-fixer fix --diff --dry-run --verbose

.PHONY: phpstan
phpstan: ## Run PHPStan static analysis
	vendor/bin/phpstan analyse

.PHONY: rector
rector: ## Run Rector to refactor code
	vendor/bin/rector process

.PHONY: rector-dry
rector-dry: ## Run Rector in dry-run mode
	vendor/bin/rector process --dry-run

.PHONY: test
test: ## Run PHPUnit tests
	bin/phpunit

.PHONY: lint
lint: ## Run linting checks
	composer validate --strict
	bin/console lint:container
	bin/console lint:yaml config --parse-tags
	bin/console lint:twig templates

.PHONY: quality
quality: cs phpstan lint ## Run all code quality checks

##
## Development
##

.PHONY: cache-clear
cache-clear: ## Clear cache
	bin/console cache:clear

.PHONY: assets
assets: ## Install assets
	bin/console assets:install public

.PHONY: db-update
db-update: ## Update database schema
	bin/console doctrine:schema:update --force

.PHONY: db-migrate
db-migrate: ## Run migrations
	bin/console doctrine:migrations:migrate --no-interaction

.PHONY: db-fixtures
db-fixtures: ## Load fixtures
	bin/console doctrine:fixtures:load --no-interaction

##
## Docker
##

.PHONY: docker-up
docker-up: ## Start Docker containers
	docker-compose up -d

.PHONY: docker-down
docker-down: ## Stop Docker containers
	docker-compose down

.PHONY: docker-build
docker-build: ## Build Docker containers
	docker-compose build

.PHONY: docker-logs
docker-logs: ## Show Docker logs
	docker-compose logs -f

.PHONY: docker-exec
docker-exec: ## Execute command in app container (make docker-exec cmd="bin/console cache:clear")
	docker-compose exec app $(cmd) 