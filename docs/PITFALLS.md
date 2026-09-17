# Pitfalls — where this stack already burned us

> **Read this BEFORE touching Redis, queues, webhooks, routing, file uploads, the test setup or a
> deploy.** Everything here is paid for: each row cost at least one round of rework, and several
> cost hours of production downtime that nobody detected automatically. None of it can be derived
> from reading the code — that is exactly why it lives in a file instead of in someone's memory.

## Golden rule

Before you call something "done", ask what part of it you have **not** observed with your own eyes
in the same environment the owner will use. Green tests are not a browser. A green deploy is not a
running container. A 200 from `/up` is not a working site. Whatever is left unobserved is where the
next round of rework comes from.

## Already burned

| # | Trap | Symptom | Fix / barrier |
|---|------|---------|---------------|
| 1 | **Redis without timeouts** — `timeout: 0.0` is infinite | Redis hiccups → every HTTP request hangs until `max_execution_time` → fatals flood the error channel, site fully down (happened 3×) | `timeout` on the `default` connection and `timeout` + `read_timeout` on `cache` in `config/database.php`. **No `read_timeout` on the queue connection** — Horizon uses blocking pops and would die every few seconds |
| 2 | **Queue Redis with `allkeys-lru`** | Jobs vanish under backlog: lost work, sudden logouts, and *zero* errors anywhere | The instance holding the queue and sessions runs `noeviction`; caching gets its own instance/db with an LRU policy. Never co-locate that Redis on the worker host — a job storm kills the box and takes Redis with it |
| 3 | **Jobs calling external HTTP on the default queue** | A hung third-party host fans out into `more than 1024 concurrent queries` in dockerd's resolver, conntrack exhausted, the whole VM's network dies | A separate bounded Horizon supervisor (small `maxProcesses`) for external work + `App\Support\CircuitBreaker` + `connectTimeout` and a `retryUntil` on every outbound call. Capping `$tries` alone does not help; capping *concurrency* does |
| 4 | **`$tries = 1` with release-based middleware** | `MaxAttemptsExceededException` before `handle()` ever runs; `failed()` then wrote a business status for 224 of 235 records that had never been processed | `RateLimited` / `WithoutOverlapping` `release()` counts as an attempt → `$tries >= 2`. In `failed()`, tell a queue-level exception from a real one and never persist domain state on the former |
| 5 | **`/up` lies** | Cloud/uptime checks stayed green while the site was down — every incident was reported by a human, once 7.5 h late | `/healthz` that actually pings Redis and the DB and returns 503; point uptime checks at it, not at `/up` |
| 6 | **SPA catch-all shadows backend routes** | `/admin`, `/horizon`, `/pulse` start returning `index.html` depending on route registration order or `route:cache`; every bot scan boots a Redis session | Catch-all with a negative regex for backend prefixes (`api\|sanctum\|up\|healthz\|admin\|filament\|livewire(?:-[^/]*)?\|horizon\|pulse\|mcp`) anchored with `(?:/\|$)` — otherwise `/administrators` and `/upload` get swallowed too. The `livewire` suffix is not optional cosmetics: Livewire 4 derives its prefix from `APP_KEY` and serves from `/livewire-<8 hex>`, so a literal `livewire` stops matching and the hole is invisible while registration order happens to favour Livewire. Guarded by a test in `tests/Feature/AdminPanelRouteTest.php` |
| 7 | **Container env beats `phpunit.xml`** | Tests silently run as `local` against the *dev* Redis/DB; parallel workers flake; hours lost on "why is this env wrong" | Every critical `<env>` in `phpunit.xml` needs `force="true"` **and** a matching `<server>` entry — Laravel reads `$_SERVER` first. Test DB is a separate database, never the dev one |
| 8 | **Parallel Pest × `max_locks_per_transaction`** | Random `QueryException` on teardown in code that did not change | Postgres default is 64; parallel teardown drops dozens of tables in one transaction. `command: postgres -c max_locks_per_transaction=256` in compose |
| 9 | **`'throw' => false` on a disk** | Uploads fail *silently* in production — `store()` returns `false`, no exception, no log, empty bucket, discovered by accident weeks later | Keep `throw` on (`FILESYSTEM_THROW`). If a disk must stay silent, the caller logs `store() === false` itself. On object storage with uniform bucket-level access also set the `visibilityHandler`, or every write fails on ACLs |
| 10 | **Deploying the `:latest` tag** | Green deploy, old code: the registry's read-after-write lag served a stale digest and the worker ran six-day-old code | Deploy an immutable tag (git SHA) and compare the running container's digest with the registry after the deploy. Same class: `docker compose pull` skips services behind a profile unless you pass `--profile` |
| 11 | **Seeder with a literal password + `db:seed --force` on deploy** | Every release reset the production admin's password to the value committed in the repo | Guard every user-creating seeder with `if (! app()->environment('local', 'testing')) return;`. Production accounts are created by hand. No credentials in `docs/`, ever |
| 12 | **Synchronous external call inside a webhook** | Parser OOM → 503 after 40 s → PHP fatal at 30 s → the provider retried the "stuck" update every 2 min forever, flooding the error channel. The code had not changed at all | A webhook answers 200 immediately and queues the work. Have the "drop pending updates" reset command written down before you need it |
| 13 | **Manual infra fix that never reached IaC** | `terraform apply` silently re-armed a closed incident (memory back to 1 Gi, `nf_conntrack_max` back to 65536) | Any imperative fix gets codified in the same sitting; plan targeted, never blind full-apply |
| 14 | **Heavy pre-commit hook calling docker** | `docker: not found`, `make: Error 127` — commits blocked from agent sessions, where the hook's `sh` has a stripped PATH | The test gate lives in CI. A hook must degrade gracefully without docker |
| 15 | **Docker leaves root-owned files** | `EACCES` on the next build, the IDE complains, and the cause is nowhere near the code | `make fix-perms` after any container build. If a directory itself is root-owned, nothing inside it can be created — including by your agent |
| 16 | **Sanctum's guard caches the user inside one test** | A test revokes a token (`PersonalAccessToken::count()` is 0) and the very next call with that token still returns 200 — the "logout does nothing" bug that is not there | `RequestGuard::setRequest()` does not clear the resolved user, and the test process reuses one container. Call `$this->app['auth']->forgetGuards()` between two authenticated calls in the same test. Production is unaffected (fresh process per request; Octane flushes auth state) |
| 17 | **A named volume keeps the ownership it was created with** | After the FrankenPHP bump the `app` container restart-looped on `mkdir /data/caddy/pki: permission denied`. The image chowns `/data` to `www-data` and had done so for months — but Docker seeds a named volume from the image **only while that volume is empty**, and `docker compose down` never removes it. The old root-owned `caddy_data` had been shadowing the fixed image the whole time; nothing broke until a newer Caddy started provisioning its local CA | Rebuilding does not help — the volume is not part of the image. Chown it in place: `docker run --rm -v <project>_caddy_data:/data -v <project>_caddy_config:/config alpine chown -R $(id -u):$(id -g) /data /config`. Same trap for any volume whose image-side owner changed after the first `up` |
| 18 | **Laravel's `notifications` stub stores `data` as `text`** | Every page of the admin panel died with `SQLSTATE[42883]: operator does not exist: text ->> unknown` — not just the bell. Filament's notification list filters on `data->format`, Laravel turns that into `data->>'format'`, and Postgres has no `->>` for `text`. Invisible until something actually queries inside the column, so it can ship and sit quiet for months | `$table->json('data')` in the migration. Laravel's own `make:notifications-table` stub writes `text()`, so this is wrong in every fresh project too — check it whenever a panel or a package reads a field out of a JSON column on Postgres |

## Adding a row

A trap earns a row when it cost a round of rework and could not have been deduced from the code.
Add it **in the same commit as the fix**, one line, filling all three columns — trap, the symptom as
it actually appeared, and the barrier that now prevents it. Rewrite a row when the barrier changes;
delete it when the barrier became impossible to remove (then it belongs to the code, not here).
Traps that are specific to one runtime (a mobile WebView, a serverless target) get their own file,
`docs/<target>-pitfalls.md`, plus a trigger line in `CLAUDE.md`.
