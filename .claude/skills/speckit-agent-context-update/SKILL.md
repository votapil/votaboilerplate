---
name: speckit-agent-context-update
description: Refresh the managed Spec Kit section in the coding agent context file
compatibility: Requires spec-kit project structure with .specify/ directory
metadata:
  author: github-spec-kit
  source: agent-context:commands/speckit.agent-context.update.md
---

# Update Coding Agent Context

Refresh the managed Spec Kit section inside the active coding agent's context/instruction file (e.g. `CLAUDE.md`, `.github/copilot-instructions.md`, `AGENTS.md`).

## Behavior

The script reads the agent-context extension config at
`.specify/extensions/agent-context/agent-context-config.yml` to discover:

- `context_file` — the path of the coding agent context file to manage.
- `context_markers.start` / `.end` — the delimiters surrounding the managed section. Defaults to `<!-- SPECKIT START -->` and `<!-- SPECKIT END -->` when the field is missing.

It then creates, replaces, or appends the managed block.

**Template deviation from upstream (deliberate):** the block carries a *stable* pointer — the
`specs/` layout and the rule for finding the active feature — never a concrete feature number.
Upstream wrote the path of the most recently modified `specs/*/plan.md` into the rules file, which
made every later session open a finished feature's plan as if it were the current context, and rotted
silently once that feature merged. Do not "restore" the plan-path behaviour.

If `context_file` is empty or the file cannot be located, the command reports nothing to do and exits successfully.

## Execution

- **Bash**: `.specify/extensions/agent-context/scripts/bash/update-agent-context.sh [plan_path]`
- **PowerShell**: `.specify/extensions/agent-context/scripts/powershell/update-agent-context.ps1 [plan_path]`

The optional `plan_path` argument is accepted for CLI compatibility with upstream and ignored.