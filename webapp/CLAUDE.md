# webapp/ — Frontend Rules (Vue 3 + Nuxt 4 + Vuetify)

You are a Senior Frontend Engineer here. The PHP rules from the repository root do not apply to
this directory; the API contract does.

Every rule below is written as a threshold or a check, not as advice. A rule that cannot fail a
test is a rule that gets ignored — that is the single most expensive lesson behind this file.

## Architecture: pure core, thin wrapper

**Logic goes in `app/utils/*.ts` with no Nuxt, Pinia, Vuetify or browser-global import.
Side effects go in the composable or store on top of it.**

This is the only structure here that scaled. `app/utils/tokenStorage.ts` takes its storage
backend as an argument and `composables/useTokenStorage.ts` picks the real one;
`app/utils/apiErrors.ts` parses the error envelope and `composables/useApi.ts` decides what to
do with it. The result is that the interesting code is testable in a plain node process with no
runtime to boot and no mocking ceremony — which is why the tests still exist a year later.

When you are about to write a `if (import.meta.client)` inside a function full of business
rules, that is the signal to split it in two.

## Testing

- `npm test` runs everything in `tests/unit/`. **Never exclude a spec from the run.** A test
  that is written and switched off looks like coverage and asserts nothing; delete it or fix it.
- A formatter or a validation rule that ALSO exists on the backend is a **mirror**. Its spec
  must say so and must assert the server's exact expected output — see
  `tests/unit/passwordPolicy.spec.ts`. A mirror without a test drifts, silently, and the copy
  the user sees is the one that lies.
- Component tests need a Nuxt runtime and are out of scope until something actually needs one.
  Keep components thin enough that the logic is tested one layer down.

## Component size — 400 lines, enforced

`tests/unit/componentSize.spec.ts` fails when any `app/**/*.vue` exceeds **400 lines**. Past
that a file stops fitting in one head and two people can no longer work on the same screen.
When it fires, extract: a child component, a composable, or a pure function into `app/utils`.
Raising the limit is a decision about the whole codebase and belongs in a commit message.

## Plugins

Nuxt 4 scans **`app/plugins/` and nothing else**. A `webapp/plugins/` directory is ignored in
silence — no warning, no error. Read `app/plugins/README.md` before adding one, and check
`.nuxt/types/plugins.d.ts` afterwards to confirm Nuxt found it.

## Vue 3

- `<script setup lang="ts">` always. No Options API, no global mixins.
- `ref` for primitives, `reactive` for a form-shaped object. Prefer `computed` over `watch`.
- `defineProps<{...}>()` / `defineEmits<{...}>()` with types, never the runtime object form.
- Never mutate a prop.

## Nuxt 4

- Rely on auto-imports. Everything exported from `app/utils/**`, `app/composables/**` and
  `app/stores/**` is available without an import statement.
- **One export site per name.** Do not re-export a util from a store or a barrel file:
  auto-import then reports "Duplicated imports X", picks one at random, and the bug surfaces as
  a function that is subtly not the one you edited.
- All API traffic goes through `useApi()`. Not `useFetch`, not a bare `$fetch`, not axios — the
  Bearer token, `Accept-Language`, the 401 redirect and the error toast live in exactly one
  place, and a call that bypasses it silently loses all four.

## State

Pinia, setup syntax. Permissions come from `GET /api/v1/auth/me` in ONE request and are checked
locally with `authStore.can('x.y')` / `hasRole()`. Never add an endpoint that answers "may I?".
Permission names used by the frontend are mirrored in `app/utils/permissions.ts` — add the case
there and in the backend enum in the same commit.

## Authorisation on pages

Hiding a nav item is cosmetics; the URL is still typeable. A page that needs a permission
declares it:

```ts
definePageMeta({ permission: PERMISSIONS.adminAccess })
```

`middleware/auth.global.ts` enforces it. See `app/pages/admin-area.vue`. The API enforces the
same permission — this only saves the user from landing on a screen that would 403.

## TypeScript

Strict everywhere. On generated types, be honest about what pays off: **generate enums and
reference data** from the backend (`make artisan typescript:transform` → `types/generated.d.ts`)
— a string-literal union of permission names is worth having. **DTOs are described on the
frontend**, in the store that owns them. The opposite rule ("never hand-write an interface")
was tried and produced a 15-line generated file next to forty hand-written interfaces.

## i18n — every user-facing string, EN + RU

- No literal text in a template. `$t('key')` / `t('key')` from `useI18n()`.
- Add the key to **both** `i18n/locales/en.json` and `ru.json` in the same edit.
  `tests/unit/i18nParity.spec.ts` fails on a key present in one file and missing from the other,
  and on an empty translation.
- Group by feature: `section.subsection.key`. Keep each file alphabetically ordered inside a
  group so a merge conflict is a real conflict.
- Server messages are already localised — show `data.message` from the API rather than
  translating a status code on the client.

## Styling

- The theme tokens and component defaults live in `nuxt.config.ts`. **Use tokens** (`surface`,
  `on-surface-variant`, `outline`, `primary`) — a hard-coded hex in a component is a bug
  report waiting for dark mode.
- If you find yourself repeating the same props on a component (`variant="outlined"
  density="comfortable"`), that belongs in `vuetifyOptions.defaults`, not in the fiftieth file.
- No Tailwind. Vuetify's utility classes plus the defaults block cover it, and mixing the two
  systems produces specificity fights nobody wins.

## UX

Mobile-first and one-handed. Primary navigation lives at the bottom (`app/layouts/default.vue`).
If an action takes more than three taps, the design has failed — change the design, not the
copy. Wrap every mutating submit handler in `singleFlight()`: a double tap on a phone is the
normal case, not the edge case.
