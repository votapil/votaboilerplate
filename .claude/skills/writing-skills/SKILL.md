---
name: writing-skills
description: Use when the user asks to create, fix or review a skill in .claude/skills, wonders why an existing skill never fires, or wants to turn a lesson into something reusable. Covers trigger-shaped descriptions, one-skill-one-operation, chaining, the CLAUDE.md boundary and when not to write a skill at all. Триггеры — «напиши скилл», «почини скилл», «скилл не вызывается», «оформи это как скилл», «нужен ли тут скилл», «куда записать этот урок».
---

# How to write a skill that actually gets loaded

Measured over six months on the project this template came from: **28 skill invocations against 3101
shell commands**, 55 of 73 installed skills never fired once, and every skill whose description
described a *role* fired exactly zero times. The skills were visible in every session. Visibility is
not invocation.

Three causes, three rules.

## 1. The description is a trigger, not a job title

`description` is the only thing the model sees before deciding to load the skill. It has to overlap
with **the sentence a user actually types**.

| Never fired | Fires |
|---|---|
| `Senior Laravel Engineer role for production-grade solutions` | `Use when the user asks to commit or push work…` |
| `Expert AI Testing Assistant for Laravel (Pest PHP focus)` | `Use when writing or fixing a Pest test… Триггеры — «напиши тест», «тесты падают»` |
| `Outlines best practices for Octane, Horizon and Actions` | `Use when work touches queues, jobs, workers… «падает через раз», «утечка состояния»` |

The formula:

```
Use when the user asks to <operation> — <the two or three situations that produce it>.
<One line on what the skill does.>
Триггеры — «<phrase>», «<phrase>», «<phrase>».
[<Neighbouring skill> для <the adjacent case>.]
```

- Trigger phrases in **the language the user writes in**. Nobody types "act as a Senior Engineer";
  they type «залей на ревью» or "tests are red".
- Include the misspelled, casual, angry form. That is the form that shows up at 2am.
- Name the neighbouring skill and its boundary, so the model picks one instead of both or neither.
- Say what the skill is *not* for when a close sibling exists ("user bug reports are a different job").

## 2. Do not repeat what the rules file already says

If a rule is in `CLAUDE.md`, it is already in context, and the model has no reason to load a skill
that restates it — this is measurable, not theoretical: the procedures duplicated between the rules
file and a skill were the skills with zero calls.

Split it this way:

| Content | Home |
|---|---|
| Always true, one or two lines, needed on every task | `CLAUDE.md` |
| A procedure with steps, commands and gates | a skill |
| A reference table consulted occasionally | `docs/`, linked from the skill |
| Environment addresses and access | `docs/LINKS.md` |

From `CLAUDE.md`, point at a skill in one line — never inline its steps.

## 3. One skill, one repeating operation

A skill is a runbook for something that happens again and again, not an encyclopedia of a framework.
If it has no verbs and no commands, it is documentation; put it in `docs/`.

- Under ~150 lines. Long files get skimmed, and their middle is not read.
- Concrete commands that were actually run, with what the output means.
- Bulk material goes in `reference/` next to `SKILL.md` and is loaded on demand.

## 4. Chain skills; do not hope they are all remembered

The skills that fired reliably were **links in a chain started by one entry skill**. Nobody remembers
sixteen names. So the entry skill (here: `feature-workflow`) says, at the step where it matters,
"load skill X now" — and X gets loaded because the chain reached it, not because the user recalled it.

**Two skills covering the same step is worse than none.** Installing a pack (Spec Kit here) next to
hand-written skills gives you two TDD gates, two commit procedures and two branching models; the model
then picks one at random and the user cannot tell which rules are in force. When an overlap is
unavoidable, state the boundary in **both** descriptions — "inside a Spec Kit run, X handles this" —
so the choice is written down instead of guessed.

## 5. Mechanics that silently break a skill

- **Directory with `SKILL.md`.** A loose `my_skill.md` inside `skills/` is not a skill to any harness.
- **`name` must equal the directory name.** They drifted once in the donor project; that skill was
  never invoked, only read as a plain file.
- Valid YAML frontmatter with both `name` and `description`. A missing `name` is a common defect.
- Marking a skill user-invokable only means a human must type it. Sixteen such design skills were
  installed on the donor project and none were ever typed.

## 6. Vendored skills

An outside skill is copied in its own commit and gets a `NOTICE.md` next to it: source, license,
date, and every local modification. Never patch a vendored skill in place without recording it — the
next update silently reverts the patch.

## 7. When *not* to write a skill

| The lesson is… | Put it… |
|---|---|
| a one-line rule that always applies | `CLAUDE.md` |
| a trap in one specific place | a comment at that place, plus a test |
| a fact that will be looked up again | `docs/`, one file, kept current |
| a repeating multi-step operation with gates | a skill |
| something used once | nowhere — do it and move on |

The reflex "write it in the rules" is how a rules file grows to two hundred lines and stops being
read. The reflex "make it a skill" is how a directory grows to seventy-three skills and stops being
searched. Route each lesson deliberately.

## 8. Before you commit a new skill

1. Write down three sentences a user might type to reach it. Do any of them overlap the description?
   If not, rewrite the description, not the body.
2. Does anything here already live in `CLAUDE.md`? Delete it from one of the two.
3. Does a neighbouring skill cover part of it? Name the boundary in both descriptions.
4. Is any command in it one you actually ran, or one you assumed exists? Run it.
