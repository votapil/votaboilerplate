---
name: env-doctor
description: Use when the local environment misbehaves rather than the code — a make or docker command not found, an empty /app inside the container, artisan missing inside a running container, tests red without a code change, root-owned files after a build, a stale frontend cache, a wrong Node version. Триггеры — «make test не работает», «докер не отвечает», «всё сломалось», «тесты падают без причины», «permission denied», «контейнер пустой», «не собирается фронт».
---

# Diagnosing the environment, not the code

Work down the ladder in order. Each rung is cheap, and a failure high up makes everything below it
lie. **Do not debug application code until this ladder is green** — the most expensive sessions on
record were spent chasing a "bug" that was a dead Docker daemon.

## 1. Is the daemon there?

```bash
docker version --format '{{.Server.Version}}' 2>&1 | tail -1
```

"Cannot connect to the Docker daemon" / "docker: command not found" → the engine is not running. On
WSL that usually means Docker Desktop is not started on the Windows side, or WSL integration is off
for this distribution. Start it, wait for it to report healthy, and re-check — every `make` target
here goes through docker, so nothing else can work first.

## 2. Are the containers up and healthy?

```bash
docker compose ps
make up
```

Anything `restarting` or `unhealthy` — read its log before anything else:

```bash
docker compose logs --tail=50 app
docker compose logs --tail=50 postgres
```

## 3. Is the code actually mounted?

After a daemon restart a bind mount can come back empty while the container still runs. The symptom
is confusing because the container is "up":

```bash
docker compose exec app ls -la /app | head
docker compose exec app php artisan --version
```

`Could not open input file: artisan` or an empty listing = a dead mount, not a missing file. Fix by
recreating the containers:

```bash
make down && make up
```

## 4. Who owns the files?

Docker builds write as root; your editor then cannot save and PHP cannot write to `storage/`.

```bash
ls -ld storage/logs bootstrap/cache
find . -maxdepth 2 -user root -not -path './vendor/*' -not -path './webapp/node_modules/*' | head
make fix-perms
```

## 5. Is the test database there, and is anyone else using it?

The suite runs against a **separate** database, created by the postgres init script on the volume's
first start. A volume that predates that script has no such database and every test dies at boot:

```bash
make test-db                                       # rescue hatch: create it on an existing volume
```

Two test runs at once share that one database and produce failures that have nothing to do with your
change — usually a query exception or a duplicate key in a test that passes on its own.

```bash
ps aux | grep '[p]est' | wc -l                     # more than one run in flight?
docker compose exec -T postgres psql -U app -d app \
  -c "select datname, count(*) from pg_stat_activity group by 1;"
```

Before believing a red suite, run the one failing test alone. If it passes alone, this is your answer.

## 6. Stale caches

```bash
make artisan args="optimize:clear"     # config, routes, views, translations
```

Frontend: a stale build cache survives a restart and produces errors that point at code which is
already correct. Stop the dev server, remove `webapp/.nuxt` and `webapp/node_modules/.cache`, start
it again.

## 7. Toolchain versions

```bash
grep -n 'image: node' docker-compose.yml       # the version the project actually builds with
docker compose run --rm -T webapp node --version
docker compose exec app php --version
node --version                                 # only matters if you run npm on the host
```

The container is the source of truth. A host Node older than the container's produces failures that
point at application code and are really a toolchain mismatch — so run npm inside the container
(`make webapp-shell`, `make build`, `make test-front`) rather than on the host.

## 8. Config drift

```bash
ls -la .env docker/.env || make init          # a fresh clone has neither — `make init` writes both
diff <(sed 's/=.*//' .env.example | sort -u) <(sed 's/=.*//' .env | sort -u)
```

A variable present in the example and missing from your `.env` is a feature that will silently take
its default and behave differently from everyone else's machine.

## Report back

Say **which rung was broken and what fixed it**, so the next occurrence is recognised in one step
rather than re-diagnosed. If the same rung breaks repeatedly, that is a note for `docs/` — not a
thing to rediscover monthly.
