# specs/

Spec-driven feature artefacts live here, one directory per feature: `specs/<NNN>-<slug>/`.

This directory ships **empty on purpose**. Nothing here is scaffolding you need to keep — the first
directory appears when you start your first spec-driven feature.

## Starting a feature

```
/speckit-specify   <what you want to build>   # creates the branch + specs/<NNN>-<slug>/spec.md
/speckit-clarify                              # optional: resolves open questions inside spec.md
/speckit-plan                                 # plan.md (+ research/data-model/contracts if needed)
/speckit-tasks                                # tasks.md, dependency-ordered
/speckit-implement                            # executes tasks.md
```

The numbering and the branch are created by `.specify/scripts/bash/create-new-feature.sh`; do not
hand-create directories here, or the next feature will reuse your number.

## Before you start one

Read `.specify/when-to-use-speckit.md`. Most changes belong in the direct lane and should never get
a directory here. A half-finished spec is a document that lies about the state of the code.

## Housekeeping

- One feature, one number. `specs/030-a/` and `specs/030-b/` existing at once means the numbering
  was bypassed.
- `tasks.md` is ticked in the same commit as the code, or deleted.
- Merged and stable features move out of this directory (for example to `archive/`) so the working
  set an agent reads each session stays small.
- Commit the synthesis, not the raw research dumps that produced it.
