/**
 * Recover a tab that was left open across a deploy.
 *
 * A deploy rotates the JS chunk hashes. A client still holding the old index.html asks for a
 * chunk whose hash no longer exists; the SPA fallback answers `200 text/html`, so the module
 * loader rejects it with "'text/html' is not a valid JavaScript MIME type" and the lazy screen
 * simply never opens. To the user the button "does nothing" — no error, no spinner. It is a
 * miserable bug to diagnose and thirty lines to prevent, so any SPA that deploys more than
 * once a week wants this from day one.
 */
export default defineNuxtPlugin((nuxtApp) => {
    const RELOAD_KEY = 'app:chunk-reload-at'

    const recover = () => {
        // At most one reload per 10s. If reloading cannot fix it (a genuinely broken chunk) we
        // must not loop; a later deploy, minutes away, is free to try again.
        const last = Number(sessionStorage.getItem(RELOAD_KEY) ?? 0)
        if (Date.now() - last < 10_000) return
        sessionStorage.setItem(RELOAD_KEY, String(Date.now()))

        // Say why the screen is about to refresh, so it does not feel like a crash.
        try {
            const t = (nuxtApp.$i18n as { t?: (k: string) => string } | undefined)?.t
            useNotification().info(t ? t('errors.update_reloading') : 'Updating…')
        } catch {
            // i18n or the snackbar is not ready yet — reload anyway.
        }

        setTimeout(() => reloadNuxtApp(), 1200)
    }

    // Nuxt fires this when a lazy component or route chunk fails to load.
    nuxtApp.hook('app:chunkError', recover)

    // Vite fires this on window when a dynamic-import preload fails.
    window.addEventListener('vite:preloadError', (event) => {
        event.preventDefault()
        recover()
    })
})
