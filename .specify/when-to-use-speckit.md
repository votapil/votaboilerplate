# When to use Spec Kit, and when to just write the code

Spec Kit is installed in this template because it earns its keep on large features. It does **not**
earn its keep on everything, and pretending otherwise is how the process quietly breaks.

## The evidence this rule is built on

From six months of a real project that ran this exact toolchain:

- 21 features got a `specs/` directory. **8 of them never got past `spec.md`** — no plan, no tasks —
  and shipped to production anyway. The ceremony was started and abandoned, silently.
- One feature that shipped complete (debts domain, 4 artefacts) had **0 of 16 checkboxes ticked** in
  its `tasks.md`. Another showed 28/72 while the code for several unticked tasks was already merged.
  A tracker nobody updates does not track — it misinforms.
- Only about **20% of commits** ever touched `specs/`. The other 80% were fixes, batches of user
  feedback, and hotfixes — work that never fit the pipeline and never should have.
- The heaviest feature pack weighed **504 KB** of markdown. Two files inside it were raw
  multi-agent research dumps (130 KB + 111 KB) sitting next to the 20 KB synthesis that was
  actually read.

The conclusion is not "Spec Kit is bad". It is: **pick the lane before you start, and finish the
lane you picked.**

---

## Lane 1 — Direct

Branch, write a failing test, write the code, review, merge. No `specs/` directory.

Use it when **all** of these hold:

- One reviewer can hold the whole change in their head.
- The requirement is already agreed — there is nothing to clarify with the owner.
- It touches one area (one endpoint, one screen, one job) and no data model in a way that would
  need a migration plan.
- Fits in roughly a day of work and one commit.

Typical: bug fixes, copy and i18n changes, a new field on an existing resource, refactors with no
behaviour change, dependency bumps, CI repairs, styling.

The safety net for this lane is the test suite and code review, not documents.

---

## Lane 2 — Spec-driven

`/speckit-specify` → `/speckit-plan` → `/speckit-tasks` → `/speckit-implement`, artefacts under
`specs/<NNN>-<slug>/`.

Use it when **any** of these hold:

- The requirement is still fuzzy and needs clarifying questions before code (`/speckit-clarify`).
- It introduces or reshapes a domain concept — new tables, new lifecycle, new permissions.
- It spans backend + frontend + admin, or several sessions, or several people.
- Getting it wrong is expensive: money, permissions, data migration, anything a user cannot undo.
- You want an artefact someone can read in three months to understand *why*.

Typical: a new domain area, multi-tenancy or permission model changes, a payment or billing flow, a
migration of stored data, a whole new client surface.

---

## Scaling the artefacts inside Lane 2

`spec.md` and `tasks.md` are the parts that consistently pay for themselves. Everything else is
optional and should be produced only when the feature actually needs it:

| Artefact | Produce it when |
|---|---|
| `spec.md` | always in this lane — requirements + acceptance criteria + explicit non-goals |
| `plan.md` | the approach is not obvious, or there is a real choice between designs |
| `tasks.md` | the work is more than one sitting, or more than one person touches it |
| `research.md` | you compared options and the comparison would otherwise be lost |
| `data-model.md` | new tables or a non-trivial reshape of existing ones |
| `contracts/` | another client or service consumes the API and needs a frozen shape |
| `quickstart.md` | someone has to reproduce a setup by hand |
| `checklists/` | there is a review dimension the tests cannot cover |

**Do not commit raw agent or web-research dumps.** Commit the synthesis. If the raw output mattered,
quote the three lines that mattered.

---

## Rules that keep the artefacts honest

1. **`tasks.md` is a live tracker or it is deleted.** Tick boxes as you go, in the same commit as the
   code. If you stop ticking, delete the file — an abandoned checklist is worse than none.
2. **Finish or fold.** If a feature drops out of Lane 2 halfway, either complete the artefacts or
   remove them and say so in the commit message. Do not leave a `spec.md` orphan pretending a plan
   exists.
3. **One feature, one number.** `specs/<NNN>-<slug>/` and the branch name share the number. Two
   directories with the same number means the numbering was bypassed — fix it before continuing.
4. **The spec is not the code.** When they disagree, the code wins and the spec gets corrected or
   archived. Never "fix" reality to match a document.
5. **Completed specs move out of the way.** Once a feature is merged and stable, move its directory
   out of `specs/` (an `archive/` directory works) so the agent's working set stays small.

---

## If in doubt

Start in Lane 1. Promoting a change to Lane 2 after the first surprise costs one command
(`/speckit-specify`). Abandoning Lane 2 halfway costs the whole team the ability to trust `specs/`.
