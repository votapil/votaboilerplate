import { errorMessage } from '../utils/apiErrors'

/**
 * The one way this app talks to the API.
 *
 * A thin wrapper over `$fetch` — not an HTTP client. It owns exactly four cross-cutting
 * concerns, and every one of them is here because doing it per-call goes wrong:
 *
 *  1. Bearer token on every request.
 *  2. `Accept-Language` from the ACTIVE UI locale. Without it the backend falls back to the
 *     browser's `Accept-Language`, i.e. the OS language — so a guest who switched the app to
 *     Russian still got English validation errors on login and registration. The language the
 *     user picked must travel with the request.
 *  3. 401 anywhere -> drop the token and go to /login, once, silently.
 *  4. One error surface: the toast shows the message the BACKEND already localised
 *     (`data.message`, or the first 422 field error); forms read the same 422 through
 *     `fieldErrors(e)` from utils/apiErrors.
 *
 * Everything else belongs in a store or a page.
 */

// Module scope, not per-composable: `useApi()` is called from dozens of setups, and attaching
// the visibility listener inside would add one listener per call. Throttling must be shared
// too, otherwise every caller gets its own budget and the user sees N identical toasts.
let lastWakeAt = 0
let lastNetworkErrorAt = 0
const WAKE_GRACE_MS = 3000
const THROTTLE_MS = 5000
let listenerAttached = false

const trackWakeUps = () => {
    if (listenerAttached || typeof document === 'undefined') return
    listenerAttached = true
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') lastWakeAt = Date.now()
    })
}

export interface ApiPaginationMeta {
    current_page: number
    last_page: number
    per_page: number
    total: number
}

export interface ApiPaginatedResponse<T> {
    data: T[]
    meta?: ApiPaginationMeta
    links?: Record<string, string | null>
}

export interface ApiSingleResponse<T> {
    data: T
}

export function useApi() {
    const config = useRuntimeConfig()
    const baseUrl = String(config.public.apiBase ?? '')

    trackWakeUps()

    // Resolved ONCE, here, where the Nuxt context is guaranteed. The helpers below run inside
    // `$fetch` interceptors — i.e. after an await — and calling useNuxtApp() there is how you
    // get "nuxt instance unavailable" in a place that is very hard to trace back. `locale` is
    // a ref, so reading `.value` later still gives the language the user has selected now.
    const i18n = useNuxtApp().$i18n as {
        locale?: { value?: string }
        t?: (key: string) => string
    } | undefined

    const currentLocale = (): string => i18n?.locale?.value ?? 'en'

    const translate = (key: string, fallback: string): string => {
        const text = i18n?.t?.(key)
        return text && text !== key ? text : fallback
    }

    const authHeaders = (extra: Record<string, string> = {}): Record<string, string> => {
        const headers: Record<string, string> = {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'Accept-Language': currentLocale(),
            ...extra,
        }

        const token = import.meta.client ? useTokenStorage().getToken() : null
        if (token) headers.Authorization = `Bearer ${token}`

        return headers
    }

    /**
     * A phone that just woke from sleep has no network for a moment, so a failed request there
     * is not worth a toast — the retry a second later succeeds. Suppress for a grace period
     * after the tab became visible, then show at most one message per THROTTLE_MS.
     */
    const showNetworkError = () => {
        if (!import.meta.client) return

        const now = Date.now()
        if (now - lastWakeAt < WAKE_GRACE_MS) return
        if (now - lastNetworkErrorAt < THROTTLE_MS) return

        lastNetworkErrorAt = now
        useNotification().error(translate('errors.network', 'Network error. Check your connection.'))
    }

    // Shared by every helper below, uploads included — an upload that fails must not fail silently.
    const errorHandlers = () => ({
        onResponseError({ response }: { response: { status: number, _data?: unknown } }) {
            if (!import.meta.client) return

            if (response.status === 401) {
                useTokenStorage().clearToken()
                navigateTo('/login')
                return
            }

            useNotification().error(
                errorMessage(response._data, translate('errors.unknown', 'Something went wrong. Please try again.')),
            )
        },
        onRequestError() {
            showNetworkError()
        },
    })

    const url = (path: string) => (path.startsWith('http') ? path : `${baseUrl}${path}`)

    const apiFetch = <T>(path: string, opts: Record<string, unknown> = {}): Promise<T> =>
        $fetch<T>(url(path), {
            ...opts,
            headers: authHeaders((opts.headers as Record<string, string>) ?? {}),
            ...errorHandlers(),
        })

    const apiGet = <T>(path: string, params?: Record<string, unknown>): Promise<T> =>
        apiFetch<T>(path, { method: 'GET', params })

    const apiPost = <T>(path: string, body?: unknown): Promise<T> =>
        apiFetch<T>(path, { method: 'POST', body })

    const apiPut = <T>(path: string, body?: unknown): Promise<T> =>
        apiFetch<T>(path, { method: 'PUT', body })

    const apiPatch = <T>(path: string, body?: unknown): Promise<T> =>
        apiFetch<T>(path, { method: 'PATCH', body })

    const apiDelete = <T>(path: string): Promise<T> =>
        apiFetch<T>(path, { method: 'DELETE' })

    /**
     * Multipart upload. Content-Type is deliberately NOT set: the browser has to add it
     * together with the multipart boundary, and an explicit header breaks the boundary.
     */
    const apiUpload = <T>(path: string, formData: FormData): Promise<T> => {
        const headers = authHeaders()
        delete headers['Content-Type']

        return $fetch<T>(url(path), { method: 'POST', body: formData, headers, ...errorHandlers() })
    }

    /** Items out of a `JsonResource` collection (`{ data: [...] }`) or a bare array. */
    const unwrapData = <T>(response: unknown): T[] => {
        const data = (response as { data?: unknown } | null)?.data
        if (Array.isArray(data)) return data as T[]
        return Array.isArray(response) ? (response as T[]) : []
    }

    /** Pagination meta out of a paginated collection, with a safe single-page default. */
    const unwrapMeta = (response: unknown): ApiPaginationMeta =>
        (response as { meta?: ApiPaginationMeta } | null)?.meta
        ?? { current_page: 1, last_page: 1, per_page: 15, total: 0 }

    return {
        baseUrl,
        authHeaders,
        apiFetch,
        apiGet,
        apiPost,
        apiPut,
        apiPatch,
        apiDelete,
        apiUpload,
        unwrapData,
        unwrapMeta,
    }
}
