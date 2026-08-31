/**
 * Reading the API's single error envelope — `{ message, errors?, code? }` (see the project
 * API standards). Pure, so it is unit-tested without a Nuxt runtime, and shared by the
 * `useApi()` toast path and by forms that bind per-field messages.
 *
 * Why not inline this in useApi(): every form needs the SAME 422 unwrapping to fill
 * `:error-messages`, and a second hand-rolled copy is how the two drift apart.
 */
export interface ApiErrorBody {
    message?: string
    errors?: Record<string, string[] | string>
    code?: string
}

/** `$fetch` rejects with a FetchError carrying the parsed body on `.data`. */
export function errorBody(error: unknown): ApiErrorBody | null {
    const data = (error as { data?: unknown } | null)?.data

    return data && typeof data === 'object' ? (data as ApiErrorBody) : null
}

/**
 * Laravel's 422 payload flattened to one message per field, ready for `:error-messages`.
 * Returns an empty object for every other status.
 */
export function fieldErrors(source: unknown): Record<string, string> {
    const body = errorBody(source) ?? (source as ApiErrorBody | null)
    const raw = body?.errors
    if (!raw || typeof raw !== 'object') return {}

    const out: Record<string, string> = {}
    for (const [field, messages] of Object.entries(raw)) {
        const first = Array.isArray(messages) ? messages[0] : messages
        if (typeof first === 'string' && first !== '') out[field] = first
    }

    return out
}

/**
 * The one line to show the user.
 *
 * Order matters: the FIRST field error wins over the generic message, because on a 422 the
 * generic message is "The given data was invalid" — true and useless. The backend message is
 * preferred over any client-side text: it is already translated into the user's locale by the
 * API (which is why `useApi()` sends `Accept-Language`).
 */
export function errorMessage(source: unknown, fallback: string): string {
    const body = errorBody(source) ?? (source as ApiErrorBody | null)

    const first = Object.values(fieldErrors(body))[0]
    if (first) return first

    return typeof body?.message === 'string' && body.message !== '' ? body.message : fallback
}
