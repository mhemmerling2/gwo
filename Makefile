.PHONY: start
start: erase up ## Clean current environment, recreate dependencies and spin up again

.PHONY: stop
stop: ## Stop environment
		docker compose -f docker-compose.yml stop

.PHONY: erase
erase: ## Stop and delete containers, clean volumes
		docker compose -f docker-compose.yml stop
		docker compose -f docker-compose.yml rm -v -f

.PHONY: bootstrap
bootstrap: composer-install ## Bootstrap project requirements

.PHONY: composer-install
composer-install: ## Install project dependencies
		docker compose run --rm app sh -lc 'composer install'

.PHONY: up
up: ## spin up environment
		docker compose -f docker-compose.yml up -d

.PHONY: db-indexes
db-indexes: up ## create required MongoDB indexes
		docker compose run --rm app sh -lc "php bin/console app:mongodb:setup-indexes"

.PHONY: reset-dev-db
reset-dev-db: up ## drop current MongoDB data and recreate indexes
		docker compose run --rm app sh -lc "php bin/console app:mongodb:reset-dev-data"

.PHONY: demo-users
demo-users: up ## create demo lecturer/student accounts for manual API verification
		docker compose run --rm app sh -lc "php bin/console app:users:seed-demo"

.PHONY: consume-enrollments
consume-enrollments: up ## process queued enrollment jobs
		docker compose run --rm app sh -lc "php bin/console messenger:consume async --limit=10 --time-limit=60"

.PHONY: phpunit
phpunit: ## execute project unit tests
		docker compose run --rm app sh -lc "rm -rf var/cache/test && XDEBUG_MODE=coverage ./vendor/bin/phpunit --testdox --coverage-text --colors=never"

.PHONY: coverage
coverage: phpunit ## execute tests with text coverage summary

.PHONY: coverage-html
coverage-html: ## generate HTML coverage report in var/coverage/html
		docker compose run --rm app sh -lc "rm -rf var/cache/test && mkdir -p var/coverage && XDEBUG_MODE=coverage ./vendor/bin/phpunit --coverage-html var/coverage/html --coverage-text --colors=never"

.PHONY: coverage-check
coverage-check: ## execute tests and fail if line coverage drops below 80%
		docker compose run --rm app sh -lc "rm -rf var/cache/test && mkdir -p var/coverage && XDEBUG_MODE=coverage ./vendor/bin/phpunit --coverage-clover var/coverage/clover.xml --coverage-text --colors=never && php bin/check-coverage-threshold.php var/coverage/clover.xml 80"

.PHONY: tests
tests: up phpunit

.PHONY: phpstan
phpstan: ## execute static analysis (PHPStan)
		docker compose run --rm app sh -lc "./vendor/bin/phpstan analyse --configuration=phpstan.neon.dist --memory-limit=512M"

.PHONY: qa
qa: up coverage-check phpstan ## run the full verification suite, including coverage gate

.PHONY: exec
exec: ## Gets inside a container, use 's' variable to select a service. make exec s=app
		docker compose exec $(s) bash -l

.PHONY: logs
logs: ## Look for 's' service logs, make s=app logs
		docker compose logs -f $(s)

.PHONY: network
network: ## Inspect network
		docker compose exec app ngrep -q -t -l -w -W byline '^(GET|POST|PATCH|HEAD|HTTP)'

.PHONY: help
help: ## Display this help message
	@cat $(MAKEFILE_LIST) | grep -e "^[a-zA-Z_\-]*: *.*## *" | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'
