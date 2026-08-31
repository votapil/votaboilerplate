# Local-first verification

**The fact.** A change is verified locally, in a real browser, before it is pushed anywhere. The
loop is: edit → unit tests + build → open the page on localhost and click the thing → only then
push. A staging deploy costs minutes of CI per round; the local stack applies the same change
instantly through the volume mount.

**Why it is written down.** In the project this template comes from, the owner raised this three
times in a single day, the last time in capitals. Iterating through staging was the single most
irritating habit of the whole six months — not because it was slow for the agent, but because every
round pulled the owner into waiting and re-checking.

**How to do it.**

```bash
make up                 # stack: app :8080, webapp :3000, postgres :5433, redis :6380
make test               # backend
make test-front         # frontend units
```

Then actually open the page (`http://localhost:3000`, or `http://localhost:8080/admin` for the
panel), reproduce the scenario end to end and look at the result. If a browser is not available to
you, say so explicitly instead of substituting "tests are green" for "it works".

**Related:** the push itself happens only on the owner's explicit go-ahead (`CLAUDE.md` → Working
agreement), and every iteration ends with a short "what to click by hand" list.
