# Links — where to go and how you get let in

One cheat sheet for every address of the system. Fill the placeholders on the first deploy and keep
it short: a long, half-true links page is worse than none.

> **No secrets in this file — ever.** Not a password, not a token, not a connection string with a
> real password in it. Name *where* the secret lives (secret manager, CI variable, `.env` on the
> host) and how to read it. A committed credential stays known forever; one production admin
> password lived in a seeder and got reset back on every deploy.

## Local (docker compose)

| What | URL | Notes |
|---|---|---|
| API / backend | http://localhost:8080 | `app` container, port 80 inside |
| API base | http://localhost:8080/api/v1 | Bearer token (Sanctum) |
| HTTPS (self-signed) | https://localhost:8443 | Caddy inside FrankenPHP |
| Frontend (Nuxt dev) | http://localhost:3000 | hot reload, `webapp` container |
| Admin (Filament) | http://localhost:8080/admin | web session, `admin` panel |
| Queues (Horizon) | http://localhost:8080/horizon | web session — see the gotcha below |
| Health — deep | http://localhost:8080/healthz | pings Redis + DB, 503 when degraded |
| Health — shallow | http://localhost:8080/up | PHP boot only; **do not** monitor this one |
| API docs (Scramble) | http://localhost:8080/docs/api | generated, no annotations |
| Laravel MCP server | http://localhost:8080/mcp/app | wired up in `.mcp.json` |
| Postgres | `localhost:5433` | db/user/password from `docker/.env` |
| Redis | `localhost:6380` | password from `docker/.env` |

Ports come from `docker-compose.yml` — change them there and update this table in the same commit.

## Staging

| What | URL | Access |
|---|---|---|
| App | _fill in_ | |
| Admin | _fill in_ | |
| Deploys / CI | _fill in_ | |

## Production

| What | URL | Access |
|---|---|---|
| App | _fill in_ | |
| Admin | _fill in_ | |
| Queues | _fill in_ | |
| Health check (`/healthz`) | _fill in_ | uptime monitor points here |
| Logs | _fill in_ | |
| Error alerts | _fill in_ | |
| Deploys / CI | _fill in_ | |
| Where secrets live | _fill in_ | e.g. secret manager, command to read one |
| How to roll back one release | _fill in_ | one command, written down before you need it |

## Access gotchas

- **Horizon / Pulse return 403 even to an admin.** The SPA authenticates with a Bearer token, those
  dashboards need a **web session**. Log into `/admin` first in the same browser, then open them.
  On a subdomain split, the session cookie has to be issued for the parent domain.
- **A dashboard secret is not in git.** Read it from the secret store; if you have to paste it
  somewhere, paste it in chat, not into this file.
- **Console access to production is a last resort.** Prefer read-only DB credentials with a
  statement timeout over a shell that can write.
