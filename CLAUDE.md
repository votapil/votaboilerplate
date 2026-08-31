# Project Rules — the single source of truth

Laravel 12 / PHP 8.4 backend, Nuxt 4 + Vuetify 4 in `webapp/`. The ONLY rules file; `AGENTS.md` is
a symlink to it — a second copy for another IDE always drifts.
Read on demand: **`docs/PITFALLS.md` BEFORE touching Redis, queues, routing, uploads, tests or
deploy** · `docs/LINKS.md` (addresses, access) · `docs/notes/INDEX.md` (lessons) ·
`webapp/CLAUDE.md` (frontend rules).

## Engineering Discipline

**1. Think before coding.** State your assumptions. Unclear → ask, don't guess. Several readings →
show them, don't silently pick one. Simpler way → say so, push back. Any real fork → 2–4 options
(A/B/C) with pros/cons, cost per month, effort, risk, then your pick. Nothing paid is ever proposed
without its price.

**2. Simplicity first.** Minimum code that solves the problem: nothing speculative, no abstraction
for one call site, no handling of impossible cases. 200 lines that could be 50 → rewrite.
*Precedence:* project conventions WIN over generic minimalism — `vota:crud`, Actions, `JsonResource`
and i18n are never "overengineering" here.

**3. Surgical changes.** Every changed line traces back to the request. Don't reformat or "improve"
adjacent code — match existing style. Someone else's dead code: mention, don't delete; remove only
what YOUR change made unused. Temporary debug panels die in the fixing commit.

**4. Goal-driven execution.** Define the success check first: "fix the bug" means "write a failing
test, then make it pass". Never claim done without fresh evidence in the same message — the command
and its output. Never cite a path you have not opened; spot-check paths from any subagent report
(`ls`), fabricated ones have cost whole rounds of work.

## Working agreement

- Talk to the owner in **Russian**; code, identifiers, comments, commits — English.
- Name the current branch (`git branch --show-current`) and its lag behind `main` before starting.
- **Local first:** change → unit tests + build → click through it on localhost in a real browser →
  only then push. A staging deploy is never your iteration loop.
- Push and deploy only on the owner's explicit go-ahead. `main` is production.
- Every iteration ends with **"what to click by hand"**: step → expected result (URL, screen,
  button) — not "done, deployed".
- Owner's manual steps get their own block: link, numbered steps, what NOT to click. Whatever you
  can reach yourself (CLI, cloud, DB) you do.
- Fix a bug by first reproducing the reporter's exact input, showing it reproduces, then fixing.
- Don't break what works: `make verify` (pint + pest + vitest + nuxt build) is green before a push.

## Git

- **ONE commit when the feature is FULLY complete**, Conventional Commits — not per step or phase;
  and never leave finished work uncommitted.
- Never `git add .` / `git add -A` — stage named paths. Blanket staging has committed junk before.
- A tool that commits or rewrites files by itself (plugin, hook) gets reconciled with this file:
  record here which of its parts are off, and why.

## Backend

- **Octane:** no `static` properties or variables in Controllers, Actions, Services — the worker
  keeps them between requests and leaks data between users.
- Logic beyond CRUD → `App\Actions\…`, one public `handle()`. Controllers thin; models hold
  relations, casts, simple accessors.
- No N+1: `with()` whenever a relation is read across a collection.
- `DB::transaction()` stays short — HTTP calls and heavy work happen BEFORE it. Domain events fire
  only AFTER commit (never in the closure or an observer), carrying IDs, not Eloquent objects.
- **Every migration column gets `->comment()`:** FKs describe the relation, status/enum columns list
  every value, money columns state the scale.
- API: prefix `api/v1`, controllers in `App\Http\Controllers\Api\V1`, output only via
  `JsonResource`. Error JSON: `{message, errors?, code?}`.
- Owned models: trust the global scope (an explicit `where('user_id', …)` collapses it) and never
  bypass the model trait — it is what scopes route-model binding, which resolves before middleware.
- **i18n is mandatory:** no hardcoded user-visible string, ever — `__('key')`, added to `lang/en`
  and `lang/ru` in the same commit. Forbidden: `abort(403, 'Unauthorized.')`.
- `#[TypeScript]` on anything the frontend types against, then `make ts-sync`; never hand-write
  those interfaces twice.
- Everything through `make` — never `php artisan` on the host; root-owned files → `make fix-perms`.
- Read `docs/db_schema.md` before designing a feature; after a migration regenerate it with
  `make artisan args="schema:markdown"`.
- **New entity:** migration → `vota:crud` → `make:filament-resource --generate` → Pest test. Nothing
  they produce is hand-written; both generators leave mandatory manual fixes — **`docs/RECIPES.md`**.

## Testing

- Pest, feature-first: test the endpoint's whole lifecycle — `make test args="--filter Name"`.
- A bug in regression-prone logic (calculations, scoping, state transitions, API contracts, anything
  that branches) REQUIRES a failing test first (RED), then the fix (GREEN).
- Explicitly **no** test for one-off fixes: a drifted constant, an i18n typo, pure styling — don't
  manufacture ceremony around a one-line change.

## Context hygiene — why this file stays small

- **Budget: this file ≤ 6 KB.** It loads into every session, so its size taxes every request. A
  topic over ~15 lines moves to `docs/<topic>.md` and leaves a trigger ("read X BEFORE Y").
- A lesson learned = new `docs/notes/<slug>.md` + one line in `docs/notes/INDEX.md` — never a
  paragraph appended to a growing wall of text.
- A trap that cost a round of rework = a row in `docs/PITFALLS.md`, same commit as the fix.
- `PROJECT_CONTEXT.md` is current state only: fixed sections, size caps, rules in its own header.
- One-off research is dated, lives in `docs/research/`, and moves to `archive/` when spent.
- `archive/` is cold storage — do NOT read it: it contradicts live code, and `.claude/settings.json`
  denies it.
