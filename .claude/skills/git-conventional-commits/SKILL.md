---
name: git-conventional-commits
description: Use when the user asks to commit or push work. Runs the gates first — tests, lint, and a branch guard so work never lands directly on a branch that deploys — then writes a Conventional Commits message and pushes. Триггеры — «закоммить», «сделай коммит», «запушь», «залей изменения», commit, push, «оформи коммит». Внутри прогона Spec Kit коммит делает speckit-git-commit.
---

# Commit and push

Four steps, in this order. Steps 1 and 2 are gates: if a gate fails, stop and report — do not commit
around it.

## 1. Branch guard — before anything else

```bash
git rev-parse --abbrev-ref HEAD                      # where am I?
git symbolic-ref --short refs/remotes/origin/HEAD    # what is the default branch?
```

If HEAD **is** the default branch and that branch is wired to a deploy (check
`.github/workflows/*.yml` for `on: push: branches: [<name>]`), then a push publishes. In that case:

- a genuine one-line fix may go straight in — say so explicitly and let the user object;
- anything larger branches first:

```bash
git checkout -b <type>/<short-slug>
```

Never `push --force` a branch other people or an environment may be sitting on. Force-push is only
for a disposable staging pointer — see skill `deploy-branches`.

## 2. Gates

```bash
make verify      # pint --test + pest (parallel) + vitest + nuxt build
```

One command on purpose. Run separately, one of the four always ends up skipped — usually the
frontend build, which is exactly how a broken template reaches a deploy instead of the terminal.

- All four green → continue.
- Red → stop. Report exactly what failed. Fix it, or get an explicit decision from the user. Never
  push a red suite "to fix later".
- A failure that also fails on the default branch is pre-existing: say so, name it, and continue only
  if the user agrees.
- Red without a code change of yours is an environment problem — skill `env-doctor`, then re-run.

If the change touched the UI or an endpoint, the browser check from skill `local-verify` belongs here
too — green tests do not prove a screen renders.

## 3. Review the diff

```bash
git status
git diff --stat
```

Read what is actually staged. Untracked scratch files, `.env`, dumps, screenshots and generated
artefacts do not get committed. If the diff spans two unrelated things, that is two commits.

**Granularity:** one commit per completed feature or fix — not one per step. Batch the work, then
commit once. (The project rules file owns this rule; this skill owns the format and the gates.)

## 4. Message

```
<type>[optional scope]: <description>

[optional body]

[optional footer(s)]
```

Types: `feat` · `fix` · `docs` · `style` · `refactor` · `perf` · `test` · `chore` · `ci` · `build` · `revert`.

- Imperative present tense: "add", not "added" / "adds".
- Lowercase first letter, no trailing period, ≤ ~72 characters on the subject line.
- Scope is the area, not the file: `feat(auth):`, `fix(queue):`.
- Breaking change: `!` after the type/scope **and** a `BREAKING CHANGE:` footer explaining the
  migration.

Covering several things at once:

```
feat: add tags, and fix the stats cache

- feat(tags): tag CRUD with a management page
- fix(stats): drop the over-eager breakdown cache
```

Then stage, commit, and push the **current branch** explicitly:

```bash
git push -u origin "$(git rev-parse --abbrev-ref HEAD)"
```

## Examples

- `feat(api): add the thing creation endpoint`
- `fix(queue): stop the retry storm on a failing external call`
- `test(auth): cover the 404 on someone else's id`
- `chore(deps): bump the framework patch release`
- `docs: record the staging branch model`
