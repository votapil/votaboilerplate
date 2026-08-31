# Vendored methodology skills — attribution and update policy

## What is in here

Six methodology documents that the `speckit-superb-*` bridge skills load at run time. They are
**not** Claude Code skills: nothing in `.specify/` is auto-discovered, so these files cost nothing
until a bridge explicitly reads one.

| Directory | Loaded by |
|---|---|
| `brainstorming/` | `speckit-superb-clarify` |
| `test-driven-development/` | `speckit-superb-tdd` |
| `verification-before-completion/` | `speckit-superb-verify` |
| `systematic-debugging/` | `speckit-superb-debug` |
| `receiving-code-review/` | `speckit-superb-respond` |
| `finishing-a-development-branch/` | `speckit-superb-finish` |

`speckit-superb-critique` and `speckit-superb-review` have no external dependency — their logic is
original to the superb bridge extension.

## Origin

- **Upstream:** https://github.com/obra/superpowers (`skills/<name>/SKILL.md`)
- **Vendored:** 2026-08 for this template, via an intermediate copy that shipped with the
  spec-kit `superb` bridge extension.
- **Upstream licence:** not carried in the copy these files came from. Check the upstream
  repository before redistributing them outside this template.

## Local modifications

Kept to the minimum needed to make the files work here:

- Cross-references between the documents were rewritten from `.agent/skills/<name>/SKILL.md` to
  `.specify/extensions/superb/skills/<name>/SKILL.md`.
- One line in `receiving-code-review/SKILL.md` referred to a style rule of a different agent
  harness; it was reworded to state the rule directly.

Nothing else was edited. The wording is deliberately blunt ("Iron Law", the rationalisation tables)
and that bluntness is the point — it is what makes the gates hold under pressure.

## Why they live here and not in `.claude/skills/`

**One copy, one location.** The project this template was distilled from ended up with the same
skill in three directories (`.agent/`, `.agents/`, `.claude/skills/`) with three different
checksums, so nobody could tell which version the agent had actually read. Keeping the bridge's
dependencies inside the bridge's own extension makes drift structurally impossible.

The trade-off: these files are invisible to `/skill`-style invocation. That is intended — they are
loaded by the gates, not chosen by hand.

## Updating

Re-download the file from upstream, re-apply the two modifications above, and note the date here.
Do not "sync" by hand-editing: if upstream changed the method, take the change whole.
