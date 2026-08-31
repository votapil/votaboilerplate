---
name: nuxt-vuetify-expert
description: Use when writing or reviewing anything under webapp/ — a Nuxt page or layout, a Vue component, a Pinia store, an API call from the client, a Vuetify theme or form. Триггеры — «сделай страницу», «добавь компонент», «стор для…», «поправь вёрстку», «тема», «цвета», «форма», Nuxt, Vue, Vuetify, Pinia. Внешний вид нового экрана — сначала skill frontend-design; тексты интерфейса — skill i18n-sync.
---

# Frontend: Nuxt + Vuetify + Pinia

## Check the version before you quote an API

Framework majors move faster than any skill file. **Never cite an API from memory.**

```bash
grep -E '"(nuxt|vue|vuetify|vuetify-nuxt-module|pinia)"' webapp/package.json
```

Read the majors you get, and consult the docs for **those** majors. This step exists because the
previous version of this skill had version numbers in its name and its body, both wrong within
months, and the model kept quoting a dead API from them.

## Components

- `<script setup lang="ts">` only. No Options API, no `defineComponent` wrappers.
- `defineProps<{...}>()` / `defineEmits<{...}>()` with real types. Never mutate a prop — emit.
- `ref` for values, `reactive` only for a nested object you genuinely mutate in place, `computed`
  instead of a watcher whenever the value is derivable.
- Reusable logic goes into `composables/` (`useThing()`), not into a mixin and not copy-pasted.
- A component past a screenful of template is two components.

## Nuxt

- Auto-imports are on: do not hand-import `ref`, `computed`, components or composables.
- Talk to the backend through the project's own API layer (a composable wrapping `$fetch` with the
  base URL, the bearer token and the error handling), not with a raw `fetch` scattered per component
  and not with an extra HTTP client.
- Routing comes from `pages/`. Shared chrome lives in `layouts/`.
- Types for API payloads are **generated from the backend** (`make ts-sync` → `webapp/types/…`).
  Import them; do not retype a DTO by hand — a hand-typed copy drifts silently.

## Pinia

Setup syntax, one store per domain:

```ts
export const useThingsStore = defineStore('things', () => {
  const items = ref<Thing[]>([])
  const loading = ref(false)

  async function fetchAll() {
    loading.value = true
    try { items.value = await api.get<Thing[]>('/things') }
    finally { loading.value = false }
  }

  return { items, loading, fetchAll }
})
```

- The store owns server state and the loading/error flags; the component owns nothing but view state.
- Do not re-export auto-imported helpers from a store — it produces "duplicated imports" build errors.

## Vuetify

- Use the framework's own structure (`v-app`, `v-container`, `v-row`, `v-col`, `v-card`, `v-sheet`)
  and its spacing/typography utilities before writing custom CSS.
- Colours, radii and typography live **once**, in the Vuetify theme config, plus a `defaults` block
  for component-wide props. Scattered CSS variables and per-component overrides are how a design
  system dies.
- Forms: `v-form` + `:rules`, with the message text coming from i18n, not from a literal.
- **Never mix a utility-CSS framework (Tailwind and friends) with Vuetify.** Specificity fights, and
  you end up with two spacing systems. Pick one; here it is Vuetify.

## Mobile first, one-handed

The primary target is a phone held in one hand. If a normal action takes more than about three
taps, the design is wrong, not the user. Check the layout at a narrow viewport before calling it done
— see skill `local-verify`.

## Strings

No literal user-facing text in a template or a store. Everything goes through the i18n helper and
into every locale file the project ships. See skill `i18n-sync`.
