export default defineI18nConfig(() => ({
    // Composition API only — the Options-API `$t` surface is not used anywhere here.
    legacy: false,
    // A key missing from a translation renders the English string rather than the raw key.
    // tests/unit/i18nParity.spec.ts is what keeps that from becoming a habit.
    fallbackLocale: 'en',
}))
