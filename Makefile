DC = docker compose
APP = $(DC) exec php-fpm
MANTICORE = $(DC) exec manticore

.PHONY: install build up down test composer migrate seed index

install: up composer migrate seed index
	@echo "---------------------------------------------------------------"
	@echo "Installation completed successfully!"
	@echo "API Web Server:      http://localhost:8080"
	@echo "Swagger UI Docs:     http://localhost:8080/api/v1/doc"
	@echo "OpenAPI JSON Spec:   http://localhost:8080/api/v1/doc.json"
	@echo "---------------------------------------------------------------"

build:
	$(DC) build

up:
	$(DC) up -d --build

down:
	$(DC) down

test:
	$(APP) php vendor/bin/simple-phpunit

composer:
	$(APP) composer install

migrate:
	$(APP) php bin/console doctrine:migrations:migrate --no-interaction

seed:
	$(APP) php bin/console app:seed-orders

index:
	$(MANTICORE) indexer --all --rotate
