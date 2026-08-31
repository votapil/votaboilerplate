# app/plugins/

**Nuxt 4 scans `app/plugins/` and NOTHING else.** A `plugins/` directory at the root of
`webapp/` is silently ignored — no warning, no error, the file simply never runs. That trap
cost a live project four and a half months of a client-error reporter that reported nothing
while its backend endpoint sat there receiving zero requests.

Two rules:

- Every plugin lives here. If you find a `webapp/plugins/` directory, it is a bug — move it.
- After adding one, confirm it is registered: `.nuxt/types/plugins.d.ts` lists what Nuxt found.

Filename order is execution order, which is why the session warm-up is `00.session.client.ts`.
