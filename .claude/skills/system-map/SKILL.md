---
name: system-map
description: Use when the user asks where to look at or log into something — logs, queues, the admin panel, metrics, a health check, a secret, an environment URL, "who has access". Answers from docs/LINKS.md and writes back whatever was missing. Триггеры — «какие ссылки», «где смотреть логи», «где админка», «какой секрет», «куда заходить», «какие джобы висят», «как попасть в…».
---

# The map of where things live

One file, `docs/LINKS.md`, answers "where do I go for X". It is a **living index**, not a document
that gets written once — the whole point is that the answer is never hunted for twice.

## Answering a question

1. Read `docs/LINKS.md` first and answer from it if the answer is there.
2. If it is not there, find it — `docker-compose.yml`, `.env.example`, `routes/`, the CI workflows,
   the cloud console — answer the user, **and then add the row to the file in the same turn.** The
   answer is worthless if the next person has to repeat the search.
3. If the file says something that turned out to be false, fix that line. A map that lies is worse
   than a missing map.

## What the file must cover

| Section | Rows |
|---|---|
| Local | app URL, frontend URL, admin panel, health endpoints, DB and Redis shells, how to seed a user |
| Staging | URL, what deploys there, how to log in, what data is on it |
| Production | app URL, API base, admin panel, health check, deploy workflow |
| Operations | logs, queue dashboard, metrics, error channel — and **the access requirement for each** |
| Secrets | where each secret is stored (name of the store, never the value) and the command to read it |
| Access | which role/permission each protected surface needs, and the known gotchas |

## Rules for the file

- **No secret values.** Store the retrieval command, never the password. A doc with a live admin
  password in it is a leak that outlives everyone who remembers writing it.
- **Every protected link says how to get in.** "Admin only" is not enough — say which permission,
  which login flow, and why it 403s if it commonly does (a dashboard needing a session cookie while
  the SPA authenticates with a bearer token is the classic one).
- **One file.** A second document describing the same topology will drift and contradict this one;
  the moment that happens, nobody trusts either. If a stale twin exists, delete it or make it point
  here.
- Mark anything environment-specific with the environment. Half of all confusion is a staging URL
  quoted as production.

The file already ships with the local section filled in and `_fill in_` placeholders for staging,
production and operations. Filling one of those placeholders is part of the commit that creates the
thing it points at — not a later cleanup that never happens.

## Where to look when the file cannot answer

```bash
make help                              # every task command, with its one-line purpose
grep -n 'ports:' -A3 docker-compose.yml
grep -rn 'branches:' .github/workflows/*.yml
sed 's/=.*//' .env.example | sort      # every configuration knob that exists
```

Answer from those, then write the answer into `docs/LINKS.md` before moving on.
