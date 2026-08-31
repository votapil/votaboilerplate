---
name: session-search
description: Use when the user is looking for an earlier conversation — "in which chat did we do X", "I can't find that dialog", "we already fixed this once", "what was that session called". Builds a dated index of this project's sessions with titles, greps their contents, quotes the match and returns the resume command. Триггеры — «в каком чате», «найди диалог», «мы это уже делали», «не могу найти чат», «где мы это обсуждали», «продолжить старую сессию».
---

# Finding a past session

Sessions for a project live in one directory, named after the working directory with every `/`
replaced by `-`:

```bash
SESSIONS="$HOME/.claude/projects/${PWD//\//-}"
ls -la "$SESSIONS"/*.jsonl | wc -l
```

If that directory is empty, the work may have happened with a different directory as the root — a
subproject has its own directory (`…-www-Project-webapp`). List the neighbours:

```bash
ls "$HOME/.claude/projects/" | grep -i "$(basename "$PWD")"
```

## 1. Index first — title and date per session

Cheap, and usually enough on its own:

```bash
for f in "$SESSIONS"/*.jsonl; do
  t=$(grep -m1 '"type":"ai-title"' "$f" | jq -r '.aiTitle' 2>/dev/null)
  printf '%s  %-38s %s\n' "$(date -r "$f" '+%Y-%m-%d')" "$(basename "$f" .jsonl)" "${t:-—}"
done | sort
```

Sessions that never got a title show `—`; they are usually short ones.

## 2. Grep for the thing itself

Search for a distinctive artefact — a filename, an error string, a command, a package name. Grep the
raw files: they are large (hundreds of megabytes is normal), so **grep first, parse only the hits**.

```bash
grep -l -i 'the-artifact-name' "$SESSIONS"/*.jsonl
```

A good needle is something that appears in only one conversation: a build output filename, a specific
exception class, a migration name. A common word matches everything and tells you nothing.

## 3. Quote the match so the user recognises it

```bash
F="$SESSIONS/<id>.jsonl"
jq -r 'select(.type=="user")
       | select((.message.content|tostring) | test("the-artifact-name";"i"))
       | "\(.timestamp)  \(.message.content|tostring|.[0:200])"' "$F" | head -5
```

Their own words are what makes a session recognisable — far more than a summary. Also useful:

```bash
jq -r 'select(.gitBranch != null) | .gitBranch' "$F" | sort -u | head    # which branch it ran on
```

## 4. Report

Give, for each candidate: **date · title · branch · one quoted line · the resume command**.

```bash
claude --resume <session-id>
```

Two or three candidates ranked by likelihood beat one confident wrong answer — the user recognises
their own conversation instantly, and you do not.

## Notes

- These files are the local transcript store: read-only territory. Never edit or delete them.
- They contain whatever was pasted into the chat, secrets included. Quote the minimum needed for
  recognition, and never copy a credential out of a transcript into a file.
- If nothing matches, say so and offer the index from step 1 — the user usually spots the title.
