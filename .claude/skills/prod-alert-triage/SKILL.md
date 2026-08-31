---
name: prod-alert-triage
description: Use when the user pastes a production alert, a stack trace or an error-bot message, or says production is throwing errors again. Parses the alert, finds what emitted it, pulls the logs, names a root cause, proposes a fix plus a regression test, and separately decides whether the alert itself is noise. Триггеры — «опять ошибки на проде», «падает в проде», «Error — production», «MaxAttemptsExceeded», «завалило алертами», «посмотри логи прода», stack trace.
---

# Triaging a production alert

This is about the **application falling over**, not about a user complaining. A human bug report is a
different job: it starts from a person's words, this one starts from a stack trace.

## Configure once per project

| Thing | Where |
|---|---|
| Alert channel | `<the chat/bot the error log channel writes to>` |
| Log query | `<command or console URL for application logs>` |
| Queue state | `<how to reach the worker: horizon:status, queue:failed>` |
| Metrics / health | `/healthz`, `<dashboard URL>` |

Keep this filled in `docs/LINKS.md` rather than here — see skill `system-map`.

## 1. Freeze the evidence before touching anything

Copy the alert verbatim into your notes: timestamp, exception class, message, top frames, and any
context the reporter attached (user id, job name, request id). Then get the shape of the problem
before the details:

- **How many?** One occurrence or a flood. A flood changes the priority and often the diagnosis.
- **Since when?** Line it up against the last deploy: `git log --oneline -10`.
- **Still happening?** An alert about a burst that ended half an hour ago is a post-mortem, not an
  incident.

## 2. Classify — the class decides the next move

| Class | Signature | First move |
|---|---|---|
| Genuine bug | one exception, deterministic frames, in your own code | reproduce it in a test |
| Bad input | validation/parse failure on data from outside | validate at the boundary, never trust the source |
| External dependency | timeouts, connection refused, 5xx from an API | circuit breaker + backoff, not more retries |
| Retry storm | the same job failing over and over, `MaxAttemptsExceeded` | **stop the bleeding first** (see below) |
| Infrastructure | the host or the datastore itself — connection pool, memory, DNS | capacity/config, and check what co-lives on that box |
| Noise | a scanner hitting routes that do not exist, an expected 404 | filter it at the reporter, do not "fix" it |

## 3. Stop the bleeding before finding the root cause

A storm is its own outage: a job with a high retry count that fails for an external reason will
exhaust connections, sockets or DNS for **everything else on the machine**, so the first symptom is
often something unrelated going down. If a storm is live:

```bash
<worker shell> php artisan queue:failed
<worker shell> php artisan horizon:status
```

Pause or drain the offending queue, then investigate. Fix the retry policy (few tries, real backoff,
a `failed()` that reports) as part of the fix, not as a follow-up.

## 4. Root cause, stated as one sentence

"X happens when Y, because Z." If you cannot write that sentence, you have a symptom, not a cause —
keep reading logs. Do not start patching on a guess: a wrong fix deployed to production costs a
build, a deploy and the next alert.

Trace back from the top frame to the entry point: which endpoint or job, called with what, from where.

## 5. Fix

1. Write the failing test first, at the layer where the logic lives — the trace tells you where.
   Confirm it fails **for the right reason**, then fix.
2. If the failure is unreachable by tests (a datastore going away, a container limit), fix the config
   and write down in the code or in `docs/` *why* the value is what it is.
3. Decide about the alert itself: should this even page anyone? Downgrade the level, filter the class,
   or rate-limit the reporter. An alert channel nobody reads is worse than no channel.

## 6. Ship and confirm it stopped

Deploy through the normal path (skill `deploy-branches`), then verify against reality: the alert
channel is quiet, the failed-jobs count stops growing, `/healthz` is green. "Deployed" is not
"fixed".

## 7. Write it down

If it can recur, the lesson belongs somewhere durable: a comment where the trap is, a line in
`PROJECT_CONTEXT.md`, or a note in `docs/`. A second identical incident means step 7 was skipped.
