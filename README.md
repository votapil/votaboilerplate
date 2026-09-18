# VotaBoilerplate

An opinionated Laravel 13 + Nuxt 4 starter for projects that will be written **with coding agents**.
It is not a demo app: there is no domain code in it. What it ships is the part that usually gets
improvised badly in the first weeks — a container stack that runs, a versioned API skeleton with
auth, tenant scoping and a real health check, an admin panel, a test setup that does not lie, a CI
gate, and a set of rules and pitfall notes distilled from six months of running one of these in
production.

## Stack

**Backend** — Laravel 13 / PHP 8.4 on FrankenPHP + [Octane](https://laravel.com/docs/octane),
PostgreSQL 18, Redis 8, [Horizon](https://laravel.com/docs/horizon) queues,
[Sanctum](https://laravel.com/docs/sanctum) token auth, [spatie/laravel-permission] roles,
[Filament 5](https://filamentphp.com) admin panel, [Scramble](https://scramble.dedoc.co) OpenAPI,
`votapil/votacrudgenerator` for database-first scaffolding, Pest 5 + Pint.

**Frontend** — Nuxt 4 (Vue 3, Composition API) with Vuetify 4, Pinia and vue-i18n in `webapp/`,
typed against the backend by `spatie/laravel-typescript-transformer`, unit-tested with Vitest.

**Locales** — `en` and `ru` on both sides, kept in sync by a parity test.

[spatie/laravel-permission]: https://spatie.be/docs/laravel-permission

## Quick start

Requires Docker, `make`, and nothing else on the host.

```bash
make init              # .env and docker/.env from the examples
make up                # start app, webapp, worker, postgres, redis
make composer-install
make artisan key:generate
make migrate
make test              # Pest, in parallel, against a separate test database
```

Local URLs — API `http://localhost:8080`, SPA `http://localhost:3000`, admin
`http://localhost:8080/admin`, deep health check `http://localhost:8080/healthz`. The full table,
including the Postgres and Redis ports, is in **[docs/LINKS.md](docs/LINKS.md)**.

`make help` lists every target. The one worth remembering is **`make verify`** — Pint, Pest, Vitest
and the Nuxt build in one command, i.e. exactly what CI (`.github/workflows/test.yml`) runs. Deploy
workflows are deliberately not included: wire yours up as a separate workflow with
`needs: [backend, frontend]` so nothing ever ships red.

## What an agent reads in this repository

| File | Purpose |
|---|---|
| `CLAUDE.md` | **The only rules file**, ≤ 6 KB, loaded into every session. `AGENTS.md` is a symlink to it, never a copy |
| `docs/PITFALLS.md` | Where this stack already burned us — read before touching Redis, queues, routing, uploads, tests or a deploy |
| `docs/RECIPES.md` | The generator pipelines step by step, including what the generators always get wrong |
| `docs/notes/INDEX.md` | Lessons learned: an index of one-liners, one fact per file |
| `docs/LINKS.md` | Every address of the system and how you get let in. No secrets |
| `docs/db_schema.md`, `docs/permissions.md` | Generated from the code — the only documentation that cannot silently rot |
| `PROJECT_CONTEXT.md` | Current state of the project: fixed sections, size caps, rotation rule |
| `.claude/skills/` | Skills for the stack (Laravel, Nuxt/Vuetify, testing, security, i18n) plus the spec-driven workflow |
| `.claude/settings.json` | Permission allow-list for `make`/docker, and a hard deny on reading `archive/` |
| `.mcp.json` | MCP servers: Postgres (read-only mode), Redis, and the app's own Laravel MCP endpoint |

Two conventions keep this from turning into the pile it was distilled from: **one source of rules**
(no second copy for another IDE — they drift and start contradicting each other), and **a budget on
every file that gets read automatically** (a topic longer than ~15 lines moves into `docs/` and
leaves a trigger line behind).

## Adding a feature

Database-first, and the generators do the typing:

```bash
# 1. write the migration — every column gets a ->comment()
make migrate
# 2. scaffold the API from the live table
make artisan args="vota:crud Product"
# 3. scaffold the admin resource from the same table
make artisan args="make:filament-resource Product --generate"
# 4. Pest feature test, then make verify
```

Both generators leave mandatory manual fixes — the generated route lands **outside** the
authenticated group, and a `--generate` Filament resource needs four edits before an operator can
use it. Both are written out in [docs/RECIPES.md](docs/RECIPES.md).

For larger work there is a spec-driven flow (`/speckit-specify` → `clarify` → `plan` → `tasks` →
`implement`) with its artefacts in `specs/`; see [specs/README.md](specs/README.md). Small changes do
not need it.

## What is deliberately not here

No deploy pipeline beyond the test gate, no realtime/WebSocket layer, no self-hosted monitoring
stack, no design system, no example domain. Each of those was built in the project this template
came from and each turned out to be either environment-specific or dead weight. Add them when the
product actually asks for them.
