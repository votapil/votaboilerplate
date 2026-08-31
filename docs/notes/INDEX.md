# Notes — index

Lessons learned, one fact per file. The index stays cheap to read; the detail is opened on demand.

**Adding one:** a new `docs/notes/<slug>.md` with the fact, plus **one line** here —
`[Title](slug.md) — what it is and why it matters`, newest at the bottom of its group.
Never append a paragraph to an existing file that is about something else, and never turn this index
into prose. This is the one format that did not rot over six months of a real project: a growing
single-file log did, and it reached 209 KB nobody read.

**Editing one:** rewrite the file in place. A note is current state, not a changelog.
**Deleting one:** when it stopped being true, delete the file and its line. `git log` keeps history.

## Process

- [Local-first verification](local-first-verification.md) — verify in a real browser on localhost
  before pushing; a staging deploy is not an iteration loop.
- [Spot-check subagent file paths](verify-subagent-file-paths.md) — plausible reports have cited
  files that never existed; `ls` before you plan.

## Stack

_(add notes about this project's own quirks here — the things you had to learn twice)_

## Operations

_(access, incidents, runbooks — anything that starts with "how do I even look at this?")_
