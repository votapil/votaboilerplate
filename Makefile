include docker/.env
export $(shell sed 's/=.*//' docker/.env)

EXEC_APP      = docker compose exec app
EXEC_WORKER   = docker compose exec worker
EXEC_PG       = docker compose exec postgres
EXEC_REDIS    = docker compose exec redis
EXEC_WEBAPP   = docker compose exec webapp
ARTISAN       = $(EXEC_APP) php artisan
COMPOSER      = $(EXEC_APP) composer
DOCKER_F      = docker-compose.yml

# This allows us to accept extra arguments
%:
	@:
args = `arg="$(filter-out $@,$(MAKECMDGOALS))" && echo $${arg:-${1}}`

### project setup
rebuild:
	docker compose -f $(DOCKER_F) down --remove-orphans \
	&& docker compose -f $(DOCKER_F) build --parallel \
	&& docker compose -f $(DOCKER_F) up -d

down:
	docker compose down

up:
	docker compose -f $(DOCKER_F) up -d

app-shell:
	$(EXEC_APP) sh

worker-shell:
	$(EXEC_WORKER) sh

webapp-shell:
	$(EXEC_WEBAPP) sh

pg-shell:
	$(EXEC_PG) psql -U $(POSTGRES_USER) -d $(POSTGRES_DB)

redis-cli:
	$(EXEC_REDIS) redis-cli -a $(REDIS_PASSWORD)

optimize:
	$(ARTISAN) optimize:clear

tinker:
	$(ARTISAN) tinker

artisan:
	$(ARTISAN) $(call args)

composer:
	$(COMPOSER) $(call args)

composer-install:
	$(COMPOSER) install

migrate:
	$(ARTISAN) migrate

migrate-fresh:
	$(ARTISAN) migrate:fresh --seed

ts-sync:
	$(ARTISAN) typescript:transform

schema-map:
	$(ARTISAN) schema:markdown

test:
	$(EXEC_APP) php artisan test $(call args)

watch:
	docker compose run --rm app npm run dev

build:
	docker compose run --rm app npm run build

.PHONY: rebuild down up app-shell worker-shell webapp-shell pg-shell redis-cli optimize tinker artisan composer composer-install migrate migrate-fresh ts-sync schema-map test watch build
