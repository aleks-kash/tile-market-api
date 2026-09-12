.PHONY: install build up down test migrate seed index

install:
	docker compose build
	docker compose up -d
	docker compose exec php-fpm composer install
	docker compose exec php-fpm bin/console doctrine:migrations:migrate --no-interaction
	docker compose exec php-fpm bin/console app:seed-orders
	docker compose exec manticore indexer --all

build:
	docker compose build

up:
	docker compose up -d

down:
	docker compose down

test:
	docker compose run --rm php-fpm vendor/bin/simple-phpunit

migrate:
	docker compose exec php-fpm bin/console doctrine:migrations:migrate --no-interaction

seed:
	docker compose exec php-fpm bin/console app:seed-orders

index:
	docker compose exec manticore indexer --all
