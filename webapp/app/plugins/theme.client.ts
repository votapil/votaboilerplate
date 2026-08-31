/**
 * Apply the stored theme preference at start-up.
 *
 * Hooked to `vuetify:ready` rather than done in the plugin body: Vuetify's `useTheme()`
 * requires a component setup context and throws when called from a plugin. The hook hands us
 * the instance directly. All the logic is in useAppTheme() — this file only triggers it once.
 */
export default defineNuxtPlugin((nuxtApp) => {
    nuxtApp.hook('vuetify:ready', (vuetify) => {
        const { applyTheme, getStoredPreference } = useAppTheme(vuetify.theme)
        applyTheme(getStoredPreference())
    })
})
