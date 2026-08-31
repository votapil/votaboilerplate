# NOTICE — third-party skill

`frontend-design` is **vendored**, not written for this project.

| | |
|---|---|
| Origin | Anthropic's `frontend-design` skill |
| License | Apache License 2.0 |
| Vendored | 2026-08 |
| Local changes | `description` rewritten into trigger form (adds the phrases users actually type, including Russian ones) and a pointer to the project's implementation skill. Body and `reference/` are unmodified. |

## Updating it

Replace `SKILL.md` and `reference/` from upstream, then re-apply the `description` rewrite — the
upstream description is written for a general assistant and does not fire on this project's phrasing.
Record the new date in the table above.

## Why this file exists

Vendored skills lose their provenance within months: the directory looks hand-written, nobody knows
what may be updated or on what terms, and local patches get silently overwritten on the next copy.
**Every vendored skill in `.claude/skills/` gets a `NOTICE.md` next to it** naming the source, the
license, the date and the local modifications. No notice, no vendoring.
