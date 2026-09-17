# PROJECT_CONTEXT — current state

> **This is a state file, not a diary.** In the project this template came from, the same file grew
> 0 → 209 KB in six months because the rule said "append after every feature" and nothing ever said
> "read" or "shorten". So here the rules come first and they are binding.
>
> 1. **Budget: ≤ 8 KB.** Over budget → shorten before you add.
> 2. **The six sections below are fixed.** Don't add sections, don't append below the last one.
> 3. **Every section has a line cap.** At the cap you rewrite, not append: merge, drop what is no
>    longer true, keep the newest fact.
> 4. **Rotation.** What falls out of a section either dies (it was noise) or becomes
>    `docs/notes/<slug>.md` + one line in `docs/notes/INDEX.md` (it was a lesson). Never both.
> 5. **No history.** "What we did on 12.07" belongs in git log. This file answers only: what is this,
>    what works, what is being done right now, what is next.
> 6. Update it when the answer to one of those questions changed — not after every commit.

## 1. What this project is (≤ 8 lines)

- Name:
- One sentence about what it does and for whom:
- Who the users are and how they get in (web / mobile / bot / admin only):

## 2. Stack and entry points (≤ 12 lines)

- Backend: Laravel 13 / PHP 8.4 on FrankenPHP + Octane, Postgres 16, Redis 7, Horizon.
- Frontend: Nuxt 4 + Vuetify 4 + Pinia in `webapp/`.
- Admin: Filament 5 at `/admin`. Auth: Sanctum tokens. Permissions: spatie, guard `web`.
- API base: `/api/v1`. Health: `/healthz` (deep). Addresses and access: `docs/LINKS.md`.
- Anything non-obvious about the local run:

## 3. What already works (≤ 25 lines, one line per capability)

One line = one shipped capability + where its code lives. No dates, no "we decided", no prose.

- [ ] example: user registration + password reset — `app/Http/Controllers/Api/V1/AuthController.php`

## 4. In progress right now (≤ 8 lines)

- Branch:
- Task:
- Where it stopped / what is half-done:
- What must not be forgotten before the commit:

## 5. Next step (≤ 4 lines)

- 
- 

## 6. Open questions and decisions not made (≤ 8 lines)

Only live ones. A question that got answered turns into code, a `docs/notes/` entry, or nothing.

- 
