/**
 * Session token storage — the pure core.
 *
 * The shape is the pattern this codebase leans on everywhere: a Nuxt-free core that takes its
 * side effects as an injected `backend`, plus a thin wrapper (composables/useTokenStorage.ts)
 * that picks the real backend. The core is unit-tested with a fake backend; the wrapper has
 * nothing left worth testing.
 *
 * Reads are SYNCHRONOUS from an in-memory cache because the auth store reads the token inside
 * `setup()`. An async backend (a native key store, an encrypted vault) therefore has to be
 * warmed up BEFORE any other plugin runs — that is what plugins/00.session.client.ts is for,
 * and why it is named `00.`: Nuxt runs plugins in filename order.
 *
 * Writes are queued rather than awaited, so `setToken()` stays synchronous while the backend
 * catches up in order (last write wins). `flush()` exists for logout and for tests.
 */
export const TOKEN_KEY = 'auth_token'

export interface TokenBackend {
    get(key: string): Promise<string | null>
    set(key: string, value: string): Promise<void>
    remove(key: string): Promise<void>
}

export interface TokenStorage {
    warmUp(): Promise<void>
    getToken(): string | null
    setToken(value: string): void
    clearToken(): void
    /** Await the queued backend writes (logout, tests). */
    flush(): Promise<void>
}

export function createTokenStorage(backend: TokenBackend): TokenStorage {
    let token: string | null = null
    let pending: Promise<unknown> = Promise.resolve()

    // Serialise writes: the cache updates instantly, the backend strictly in order.
    // Both handlers are the same op so one rejected write cannot stall the queue.
    const queue = (op: () => Promise<unknown>): Promise<unknown> => {
        pending = pending.then(op, op)
        return pending
    }

    return {
        async warmUp() {
            token = await backend.get(TOKEN_KEY)
        },
        getToken: () => token,
        setToken(value: string) {
            token = value
            queue(() => backend.set(TOKEN_KEY, value))
        },
        clearToken() {
            token = null
            queue(() => backend.remove(TOKEN_KEY))
        },
        flush: () => pending.then(() => undefined),
    }
}
