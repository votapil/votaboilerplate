---
name: feature-workflow
description: Use when the user asks to build a whole feature end to end — a new entity with a table, an API and a screen — without a written spec. Entry point that runs the phases in order and pulls in the other skills at the right step. Триггеры — «сделай фичу», «добавь раздел», «новая сущность», «нужен эндпоинт и экран», «запили целиком». Для фичи со спекой в specs/ вход другой — speckit-specify.
---

# Building a feature end to end

Database first, then backend, then frontend, then the strings, then the eyes, then one commit.
Do not skip forward: a screen built before the schema is settled gets rewritten.

## Which lane

There are two, and mixing them produces two half-plans. Pick one and say which:

- **This one — the light lane.** The request is clear, it fits in a day, nobody needs it written down
  first. Straight to the phases below.
- **Spec Kit — the heavy lane.** The work is large, risky or has to be agreed before it is built:
  `speckit-specify` → `speckit-plan` → `speckit-tasks` → `speckit-implement`, with the artefacts in
  `specs/`. If a spec directory for this feature already exists, that lane already owns it.

The phases below are the same engineering either way; the heavy lane just writes them down first.

## Phase 0 — Look before you build

```bash
ls database/migrations/ | tail -20        # does the table already exist?
ls app/Models/                            # does the model?
grep -rn "Route::" routes/api.php | tail  # does the endpoint?
ls webapp/app/pages webapp/app/stores     # does the screen / store?
```

Read `docs/db_schema.md` for the current schema map. If the request is ambiguous — plural or single,
who owns the record, what happens on delete — **ask now**, in one batch. A wrong assumption here costs
the whole feature.

## Phase 1 — Schema

1. `make artisan args="make:migration create_things_table"`
2. Write the columns. **Every column gets a `->comment()`** describing its business meaning — money
   columns state their scale, status columns list their values, foreign keys describe the relation.
   This is not decoration: the generator reads those comments and turns them into model docblocks and
   Resource documentation.
3. Owner column and any tenant column go in from the start, with an index — retrofitting ownership is
   a migration plus a data backfill plus a security review.
4. `make migrate`, then `make artisan args="schema:markdown"` to refresh `docs/db_schema.md`.

## Phase 2 — Backend

1. **Generate, do not hand-write:** `make artisan args="vota:crud Thing"` produces the model,
   API controller, form requests, Resource, policy and factory from the real schema.
2. ⚠️ The generator appends its route to the **end** of `routes/api.php`, outside the groups — i.e.
   with no auth and no user scope. Move that line into the correct group by hand and delete the
   appended one. Check it every single time.
3. Add the ownership trait to the model if the records belong to users, and register the table with
   the scope if it is tenant-shared.
4. Business logic beyond CRUD goes into an Action with one `handle()` — see skill `laravel-expert`.
   Queues, jobs and anything stateful — see skill `laravel-architecture`.
5. Admin screen: `make artisan args="make:filament-resource Thing --generate"` (the `--generate` flag
   is what makes it read the schema instead of producing empty stubs).
6. Tests **before** the non-trivial code, red first — see skill `laravel-testing-expert`. Minimum per
   endpoint: happy path, 401, 403, 404 on someone else's id, 422.
7. `make ts-sync` so the frontend types match what the API actually returns.

## Phase 3 — Frontend

1. New screen? Load skill `frontend-design` **before** writing the template — deciding the layout
   after the markup exists produces the generic result the user will reject.
2. Store → page → components, in that order — see skill `nuxt-vuetify-expert`.
3. Add the entry to the navigation. A feature no one can reach is not shipped.

## Phase 4 — Strings

Every user-visible string through the translation helper, in **all** locales, backend and frontend —
see skill `i18n-sync`.

## Phase 5 — See it work

`make verify` green is necessary and not sufficient. Open the screen in a browser, walk the real
scenario, look at the screenshot — skill `local-verify`. Only then commit.

## Phase 6 — Land it

One commit for the finished feature via skill `git-conventional-commits`. Update `PROJECT_CONTEXT.md`
with what now exists and what the next step is.

## Spec template

When the user wants a feature written up before it is built:

```markdown
# Feature: <name>

## Why
<the user problem, in one paragraph>

## Already exists
<tables, models, endpoints, pages that are in place>

## To build
### Data      — tables, columns (+ comments), relations
### Backend   — endpoints, Actions, permissions, tests
### Frontend  — store, pages, components, navigation
### Strings   — the i18n keys, in every locale

## Done when
<observable conditions — what the user can do that they could not before>
```
