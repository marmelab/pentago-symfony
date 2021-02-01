help: ## Display available commands
	@fgrep -h "##" $(MAKEFILE_LIST) | fgrep -v fgrep | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'

install: ## Install dependencies using composer
	docker-compose build
	docker-compose run --rm symfony bash -ci 'composer install'
	docker-compose run --rm symfony bash -ci 'composer update'

start: ## Start containers in dev environment
	docker-compose up --force-recreate -d

stop: ## Stop containers in dev environment
	docker-compose down
