# =============================================================================
# Local development entry point. Everything runs inside docker — do not call
# php/composer/npm on the host, the versions there are not the ones we ship.
#
#   make init      first run: create the env files
#   make up        start the stack
#   make verify    the one command that must be green before you push
#   make help      list everything
# =============================================================================

DOCKER_ENV := docker/.env
GOALS      := $(or $(MAKECMDGOALS),help)

# A fresh clone has no docker/.env — it is gitignored because it carries local
# credentials. A plain `include` here fails with "sed: can't read docker/.env"
# and then silently runs every target with EMPTY variables (the catch-all rule
# below swallows the error), which is how the previous template greeted every
# new project. Fail loudly with the fix instead, except for the two targets
# that must work without it.
ifeq (,$(wildcard $(DOCKER_ENV)))
ifneq (,$(filter-out init help,$(GOALS)))
$(error $(DOCKER_ENV) is missing. Run "make init" first (it copies docker/.env.example and .env.example))
endif
endif

-include $(DOCKER_ENV)
# Export ONLY real assignments: `sed 's/=.*//'` would also hand make every word
# of every comment line, and any "NAME=" written inside a comment then reaches
# docker compose as an empty variable.
export $(shell sed -n 's/^\([A-Za-z_][A-Za-z0-9_]*\)=.*/\1/p' $(DOCKER_ENV) 2>/dev/null)

# Used by the compose services to write bind-mounted files as YOU, not as root.
UID := $(shell id -u)
GID := $(shell id -g)
export UID
export GID

# -T = no TTY. Default on purpose: these targets are run from scripts, CI and
# agent sessions where "the input device is not a TTY" would abort the command.
# Interactive targets (tinker, shells) use the plain form.
EXEC_APP      = docker compose exec -T app
EXEC_APP_TTY  = docker compose exec app
EXEC_WORKER   = docker compose exec worker
EXEC_WEBAPP   = docker compose exec webapp
ARTISAN       = $(EXEC_APP) php artisan
COMPOSER      = $(EXEC_APP) composer
DOCKER_F      = docker-compose.yml
TEST_DB       = $(POSTGRES_DB)_testing

.DEFAULT_GOAL := help

# Lets a target take free-form arguments, either way:
#   make artisan migrate:fresh
#   make test args="--filter=UserTest"
%:
	@:
args = `arg="$(filter-out $@,$(MAKECMDGOALS))" && echo $${arg:-${1}}`

### ── setup ───────────────────────────────────────────────────────────────────

init: ## create .env and docker/.env from the examples (run once, before `make up`)
	@test -f $(DOCKER_ENV) || { cp $(DOCKER_ENV).example $(DOCKER_ENV) && echo "created $(DOCKER_ENV)"; }
	@test -f .env || { cp .env.example .env && echo "created .env"; }
	@echo "Ready. Next: make up && make composer-install && make artisan key:generate && make artisan migrate"

up: ## start the stack
	docker compose -f $(DOCKER_F) up -d

down: ## stop the stack
	docker compose down

restart: ## restart every container (config changes in docker/.env need `make up` instead)
	docker compose restart

logs: ## tail the container logs: make logs args="app"
	docker compose logs -f --tail=100 $(call args)

rebuild: ## rebuild the images from scratch and start
	docker compose -f $(DOCKER_F) down --remove-orphans \
	&& docker compose -f $(DOCKER_F) build --parallel \
	&& docker compose -f $(DOCKER_F) up -d

### ── day to day ──────────────────────────────────────────────────────────────

artisan: ## run an artisan command: make artisan migrate:status
	$(ARTISAN) $(call args)

composer: ## run a composer command: make composer require vendor/pkg
	$(COMPOSER) $(call args)

composer-install: ## install the PHP dependencies
	$(COMPOSER) install

migrate: ## run the pending migrations
	$(ARTISAN) migrate

migrate-fresh: ## drop everything, migrate and seed
	$(ARTISAN) migrate:fresh --seed

tinker: ## interactive REPL
	$(EXEC_APP_TTY) php artisan tinker

ts-sync: ## regenerate webapp/types/generated.d.ts from the #[TypeScript] classes
	$(ARTISAN) typescript:transform

worker-shell: ## shell inside the queue worker (debugging Horizon)
	$(EXEC_WORKER) sh

webapp-shell: ## shell inside the node container (npm install lives here)
	$(EXEC_WEBAPP) sh

# Docker writes bind-mounted files as root unless the image drops to www-data,
# and even then anything created before that lands as root. Symptoms are never
# "permission denied" where you look: `npm run build` dies with EACCES, the IDE
# refuses to save, git reports phantom changes. One command, run it and move on.
fix-perms: ## give the repository files back to your user after a docker build
	docker run --rm -v $$(pwd):/app -w /app alpine chown -R $$(id -u):$$(id -g) .

### ── checks ──────────────────────────────────────────────────────────────────

# THE gate. Four separate commands used to be run by hand, so in practice one of
# them was always skipped — most often the frontend build, which is why a broken
# .vue reached the deploy instead of the terminal.
verify: ## pint + pest + vitest + nuxt build — run this before you push
	@echo "==> 1/4 pint"
	@$(MAKE) --no-print-directory lint-test
	@echo "==> 2/4 pest"
	@$(MAKE) --no-print-directory test
	@echo "==> 3/4 vitest"
	@$(MAKE) --no-print-directory test-front
	@echo "==> 4/4 nuxt build"
	@$(MAKE) --no-print-directory build
	@echo "==> verify: all green"

# --parallel from day one: a suite only ever grows, and the two traps it walks
# into are already defused for you — postgres runs with max_locks_per_transaction=256
# (parallel teardown drops every table in one transaction) and the memory limit
# lives in phpunit.xml (paratest workers do not inherit the -d flag below).
test: ## backend tests: make test args="--filter=UserTest"
	$(EXEC_APP) php -d memory_limit=-1 artisan test --parallel $(call args)

# Runs in a throwaway container so it works even when the dev server is down.
test-front: ## frontend unit tests: make test-front args="tests/unit/money.spec.ts"
	docker compose run --rm -T webapp npm run test -- $(call args)

lint: ## format the PHP code (Pint)
	$(EXEC_APP) php vendor/bin/pint $(call args)

lint-test: ## check the formatting without writing (what CI runs)
	$(EXEC_APP) php vendor/bin/pint --test

# The test database is created on the first postgres start by
# docker/postgres/initdb/. This target is the rescue hatch for a volume that
# already existed before that script did.
test-db: ## create the test database on an existing postgres volume
	@docker compose exec -T postgres createdb -U $(POSTGRES_USER) $(TEST_DB) 2>/dev/null \
		&& echo "created database $(TEST_DB)" || echo "database $(TEST_DB) already exists"

### ── frontend ────────────────────────────────────────────────────────────────

# The `webapp` service already runs `npm run dev`; this is for when it is down.
watch: ## run the Nuxt dev server in the foreground
	docker compose run --rm --service-ports webapp npm run dev

build: ## build the Nuxt bundle (catches template errors that unit tests miss)
	docker compose run --rm -T webapp npm run build

help: ## list the targets
	@grep -hE '^[a-z][a-zA-Z0-9_-]*:.*?## ' $(firstword $(MAKEFILE_LIST)) \
		| awk 'BEGIN{FS=":.*?## "}{printf "  %-16s %s\n", $$1, $$2}'

.PHONY: init up down restart logs rebuild artisan composer composer-install migrate \
	migrate-fresh tinker ts-sync worker-shell webapp-shell fix-perms verify test \
	test-front lint lint-test test-db watch build help
