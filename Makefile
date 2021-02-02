help: ## Display available commands
	@fgrep -h "##" $(MAKEFILE_LIST) | fgrep -v fgrep | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'

init-env: ## Create .env.local for your purpose.
	cp -n ./.env.example .env.local


install: init-env ## Install dependencies using composer
	docker-compose build
	docker-compose run --rm symfony bash -ci 'composer install'
	docker-compose run --rm symfony bash -ci 'composer update'

start: ## Start containers in dev environment
	docker-compose up --force-recreate -d

stop: ## Stop containers in dev environment
	docker-compose down

create-db: ## Create database
	docker-compose run symfony bash -ci 'php bin/console doctrine:database:create --if-not-exists'

create-migration: ## Create migration for doctrine
	docker-compose run symfony bash -ci 'php bin/console make:migration'

migrate: ## Execute pending migrations
	docker-compose run symfony bash -ci 'php bin/console doctrine:migrations:migrate'

connect-db: ## Connect to database container (useful for debugging)
    docker-compose exec database psql pentago
