DOCKER_COMPOSE_DEV=docker-compose -f docker-compose.yml -f docker-compose.dev.yml
DOCKER_COMPOSE_PROD=docker-compose -f docker-compose.yml -f docker-compose.prod.yml

help: ## Display available commands
	@fgrep -h "##" $(MAKEFILE_LIST) | fgrep -v fgrep | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'

init-env: ## Create .env.local for your purpose.
	cp -n ./.env.example .env.local

autoformat: ## Auto format files
	$(DOCKER_COMPOSE_DEV) run --rm symfony bash -ci 'php ./vendor/bin/phpcbf ./src'

lint: ## Lint files
	$(DOCKER_COMPOSE_DEV) run --rm symfony bash -ci 'php ./vendor/bin/phpcs ./src'

install: init-env ## Install dependencies using composer
	$(DOCKER_COMPOSE_DEV) build
	$(DOCKER_COMPOSE_DEV) run --rm symfony bash -ci 'composer update'
	$(DOCKER_COMPOSE_DEV) run --rm symfony bash -ci 'composer install'


start: ## Start containers in dev environment
	$(DOCKER_COMPOSE_DEV) up --force-recreate -d


stop: ## Stop containers in dev environment
	$(DOCKER_COMPOSE_DEV) down

install-prod: init-env ## Install dependencies using composer in prod
	$(DOCKER_COMPOSE_PROD) build
	$(DOCKER_COMPOSE_PROD) run --rm symfony bash -ci 'composer update'
	$(DOCKER_COMPOSE_PROD) run --rm symfony bash -ci 'composer install --no-dev --optimize-autoloader'
	$(DOCKER_COMPOSE_PROD) run --rm symfony bash -ci 'php bin/console cache:clear'

start-prod: ## Start containers in prod environment
	$(DOCKER_COMPOSE_PROD) up -d --force-recreate

stop-prod: ## Stop containers in prod environment
	$(DOCKER_COMPOSE_PROD) down

test: ## Run phpunit test
	$(DOCKER_COMPOSE_DEV) run symfony bash -ci 'php bin/phpunit tests'

create-db: ## Create database
	$(DOCKER_COMPOSE_DEV) run symfony bash -ci 'php bin/console doctrine:database:create --if-not-exists'

create-migration: ## Create migration for doctrine
	$(DOCKER_COMPOSE_DEV) run symfony bash -ci 'php bin/console make:migration'

migrate: ## Execute pending migrations
	$(DOCKER_COMPOSE_DEV) run symfony bash -ci 'php bin/console doctrine:migrations:migrate' --no-interaction

migrate-prod: ## Execute pending migrations
	$(DOCKER_COMPOSE_PROD) run symfony bash -ci 'php bin/console doctrine:migrations:migrate' --no-interaction

connect-db:	## Connect to database container (useful for debugging)
	$(DOCKER_COMPOSE_DEV) exec database psql pentago

deploy: ## Deploy on amazon EC2
	rsync --delete -r -e "ssh -i ${key}" --filter=':- .gitignore' \
	./ ${user}@${host}:~/pentago
	ssh -i ${key} ${user}@${host} \
	'cd pentago &&\
	make install-prod &&\
	make start-prod &&\
	make migrate-prod'

