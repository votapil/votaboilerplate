# `useLayout` is auto-imported twice — the warning is expected

Every `nuxt build` and every `nuxt dev` start prints:

```
WARN [NUXT_B6002] useLayout is already auto-imported by Nuxt as a built-in,
     and overriding it will likely cause issues.
WARN Duplicated imports "useLayout", the one from "#app/composables/layout"
     has been ignored and "vuetify" is used
```

Nuxt 4.5 added `useLayout` as a built-in composable; Vuetify has shipped its own for years. Both
land in the auto-import registry and Vuetify's wins.

**This project calls neither.** `grep -rn useLayout webapp/app` returns nothing, so nothing here
resolves to the wrong one. The warning is noise, not a symptom — do not spend an afternoon on it.

It is upstream's to fix, and they know: Nuxt's own message points at
*"Rename useLayout in vuetify so it no longer collides with the built-in auto-import."*

**If you ever do call `useLayout`**, you will silently get Vuetify's. Import it explicitly on the
spot rather than relying on the auto-import, or the code reads as if it uses Nuxt's. This is the
general rule in `webapp/CLAUDE.md` — one export site per name — showing up between two
dependencies instead of inside our own code.
