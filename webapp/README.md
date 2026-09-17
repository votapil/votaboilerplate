# webapp — Nuxt 4 SPA

The frontend. A token-authenticated single-page app (`ssr: false`) that Laravel serves through
its catch-all route; in development it runs on its own dev server and proxies `/api` to the
API container.

## Requirements

Node **24** (`.nvmrc`, and `engines` in `package.json`). Run `nvm use` before any npm command —
an older Node fails in ways that look like application bugs.

There are two `.nvmrc` files and they must say the same thing: CI reads the one at the repository
root (`actions/setup-node` resolves `node-version-file` from there, not from `working-directory`),
while `nvm use` inside this directory reads this one. Change one, change both.

## Commands

```bash
npm install        # also runs `nuxt prepare`
npm run dev        # http://localhost:3000, /api proxied to http://localhost:81
npm run build      # production build
npm test           # unit gate (vitest, no Nuxt runtime)
```

`VITE_API_PROXY_TARGET` overrides the dev proxy target; `NUXT_PUBLIC_API_BASE` sets an absolute
API origin when the SPA is hosted apart from the API.

## Layout

| Path | What lives there |
|---|---|
| `app/utils/` | Pure logic. No Nuxt, no Pinia, no Vuetify. This is what the tests cover. |
| `app/composables/` | Thin wrappers that give the pure core its side effects. |
| `app/stores/` | Pinia stores (setup syntax). |
| `app/plugins/` | Nuxt plugins — **only** here, see `app/plugins/README.md`. |
| `app/middleware/` | Route guards; `auth.global.ts` handles auth and the permission gate. |
| `app/pages/`, `app/layouts/`, `app/components/` | UI. Max 400 lines per file, enforced by a test. |
| `i18n/locales/` | `en.json`, `ru.json` — the same keys in both, enforced by a test. |
| `tests/unit/` | Everything here runs. Nothing is excluded. |

Conventions and the reasoning behind them: `CLAUDE.md` in this directory.
