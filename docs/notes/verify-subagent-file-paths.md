# Spot-check file paths before you plan on them

**The fact.** Any path that arrives from a subagent, a research report or your own memory gets
checked against the filesystem before it becomes part of a plan: `ls`, `test -f`, `sed -n '1,20p'`
on one or two key lines. If two reports disagree, the filesystem decides — not whichever report
reads more convincingly.

**Why it is written down.** Twice, background agents returned detailed, coherent investigations
about files that did not exist. The reports looked completely plausible, were taken at face value,
and cost a full round of work each. Fabricated paths are the failure mode that looks *least* like a
failure.

**In practice.**

- Quote a path in a report only after you have opened it in the same session.
- "Done" means the command and its output are in the message, not a description of them.
- When you hand work to another agent, give it paths you have verified; when you receive work, spend
  the ten seconds to verify the paths you were given.
