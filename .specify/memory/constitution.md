# [PROJECT_NAME] Constitution

> **Status: starter template.** Fill in the placeholders on the first day of the project,
> then amend it only through `/speckit-constitution` so the sync report below stays honest.

---

## What this file is (and is not)

This constitution holds the **few rules that a feature may not violate** — the ones a plan is
checked against at the "Constitution Check" gate in `.specify/templates/plan-template.md`.

It is **not** a second copy of the day-to-day working rules. Those live in `CLAUDE.md`
(and the nested `webapp/CLAUDE.md`), which is the single source of truth for stack, commands,
naming and conventions.

Why the split is enforced: in the project this template was distilled from, the same rules were
written four times (`CLAUDE.md`, a rules file for another agent, this constitution, and a long
`HOWTOWORK.md`). They drifted, and the agent got a different answer depending on which file it
happened to open — one file said "business logic lives in Services", another said "isolate it in
Actions"; one said `docker compose exec app php artisan test`, another said `make test`.

**Rule of thumb:** if a statement can be verified by running a command or reading the code, it
belongs in `CLAUDE.md`. If it is a principle that would make you reject an otherwise working
implementation, it belongs here.

**When you amend this file:** never restate a `CLAUDE.md` rule — reference it. If a principle
here contradicts `CLAUDE.md`, one of the two is wrong; fix both in the same commit.

---

## Core Principles

Keep this list short — five or six principles maximum. A principle everybody skips is worse than
no principle at all. Mark a principle `(NON-NEGOTIABLE)` only if you are willing to send a feature
back for violating it.

### I. [PRINCIPLE_NAME] (NON-NEGOTIABLE)

[What the rule is, in one or two sentences. Then the failure mode it prevents — a real one,
not a hypothetical.]

<!--
Example shape (replace entirely):

### I. Database-First Development (NON-NEGOTIABLE)
Schema comes first: write the migration (every column carries `->comment()`), then generate the
model/controller/resource from the live schema. Hand-written CRUD drifts from the database and
the drift is invisible until production.
-->

### II. [PRINCIPLE_NAME]

[...]

### III. [PRINCIPLE_NAME]

[...]

### IV. [PRINCIPLE_NAME]

[...]

### V. Simplicity & YAGNI

Build the minimum that satisfies the accepted requirements. No abstraction for a single call site,
no configurability nobody asked for, no error handling for impossible states. Complexity that
cannot be justified at review is removed, not documented.

---

## Delivery Gates

The gates every feature passes before it is considered done. Keep them executable — a gate you
cannot run is a wish.

- **Tests**: [command, e.g. `make test`] must be green. New behaviour arrives with a failing test
  first (RED), then the code that makes it pass (GREEN).
- **Lint/format**: [command, e.g. `make lint`] must be clean.
- **Migrations**: every new column has a `->comment()`; schema documentation regenerated.
- **User-visible strings**: translated in every supported locale, no hardcoded text in responses.
- **Verification**: no completion is claimed without fresh evidence — the command was run and the
  output was read in this session.

---

## Development Workflow

Two lanes, and the choice is made **before** the work starts. The threshold is written down in
`.specify/when-to-use-speckit.md` — read it rather than re-deciding case by case.

1. **Direct lane** — small, well-understood change: branch, test, code, review, merge.
2. **Spec-driven lane** — `/speckit-specify` → `/speckit-plan` → `/speckit-tasks` →
   `/speckit-implement`, artefacts under `specs/<NNN>-<slug>/`.

An abandoned half-run of the heavy lane is worse than never starting it: the artefacts stay in the
repository and start lying about the state of the code. If you take the spec-driven lane, finish
the artefacts or delete them.

---

## Governance

- This constitution supersedes ad-hoc preferences. `CLAUDE.md` supersedes it on anything
  operational (commands, paths, naming).
- Amendments go through `/speckit-constitution`, which updates the version line below and reports
  which templates and documents need to follow.
- Every amendment names the templates it touches and either updates them in the same commit or
  records why no update is needed. A "pending" note that survives two features is a bug.
- Version numbering: MAJOR — a NON-NEGOTIABLE rule is redefined or removed; MINOR — a principle or
  section is added; PATCH — wording only.

**Version**: 0.1.0 | **Ratified**: [YYYY-MM-DD] | **Last Amended**: [YYYY-MM-DD]
