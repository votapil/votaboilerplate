/**
 * Ship uncaught client-side errors to `POST /api/v1/client-errors`, which forwards them to the
 * same channel as backend exceptions. Without this, a crash on a user's phone is invisible:
 * nobody reads a stranger's browser console.
 *
 * Deliberately hand-rolled and dependency-free:
 *  - it must never be able to crash the app it is watching, so every path swallows;
 *  - it must never report its own failure, or one broken deploy becomes a request storm;
 *  - identical messages are sent at most once a minute (a render loop throws thousands).
 *
 * `$fetch` is used directly rather than `useApi()` on purpose — useApi toasts errors and
 * redirects on 401, and an error report must stay silent.
 */
export default defineNuxtPlugin((nuxtApp) => {
    const config = useRuntimeConfig()
    if (config.public.clientErrorReporting === false) return

    const baseUrl = String(config.public.apiBase ?? '')
    const DEDUP_MS = 60_000
    const seen = new Map<string, number>()

    const send = (payload: {
        message: string
        stack?: string
        url?: string
        user_agent?: string
        component?: string
    }) => {
        const now = Date.now()
        const last = seen.get(payload.message)
        if (last && now - last < DEDUP_MS) return
        seen.set(payload.message, now)

        if (seen.size > 100) {
            for (const [key, at] of seen) if (now - at > DEDUP_MS) seen.delete(key)
        }

        const headers: Record<string, string> = {
            Accept: 'application/json',
            'Content-Type': 'application/json',
        }
        const token = useTokenStorage().getToken()
        if (token) headers.Authorization = `Bearer ${token}`

        // No await, no rethrow: reporting is best effort and must not delay or break anything.
        $fetch(`${baseUrl}/api/v1/client-errors`, { method: 'POST', headers, body: payload })
            .catch(() => {})
    }

    const context = () => ({
        url: window.location.pathname,
        user_agent: navigator.userAgent,
    })

    // 1. Errors thrown inside Vue components.
    nuxtApp.vueApp.config.errorHandler = (err, instance, info) => {
        console.error('[vue]', err)
        const error = err instanceof Error ? err : new Error(String(err))
        const name = (instance?.$options as { name?: string, __name?: string } | undefined)
        send({
            message: error.message,
            stack: error.stack,
            component: `Vue:${name?.name ?? name?.__name ?? 'Unknown'} (${info})`,
            ...context(),
        })
    }

    // 2. Rejected promises nobody caught.
    window.addEventListener('unhandledrejection', (event) => {
        const reason = event.reason
        const error = reason instanceof Error ? reason : new Error(String(reason))
        send({
            message: `Unhandled promise: ${error.message}`,
            stack: error.stack,
            component: 'UnhandledPromise',
            ...context(),
        })
    })

    // 3. Uncaught exceptions. `event.target !== window` means a failed <img>/<script> load,
    //    which is noise, not a crash.
    window.addEventListener('error', (event) => {
        if (event.target !== window) return
        send({
            message: event.message || 'Unknown error',
            stack: event.error?.stack,
            component: `GlobalError:${event.filename}:${event.lineno}`,
            ...context(),
        })
    })
})
