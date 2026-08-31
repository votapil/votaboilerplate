import { createTokenStorage, type TokenBackend, type TokenStorage } from '../utils/tokenStorage'

/**
 * The thin wrapper over utils/tokenStorage: it only picks a backend.
 *
 * `localStorage` is the web backend. If this app ever ships inside a native shell, swap the
 * backend HERE (a WebView's localStorage can be evicted by the OS, which reads to the user as
 * a random logout) — nothing else in the app changes, because everything else talks to the
 * synchronous core.
 */
const localStorageBackend: TokenBackend = {
    async get(key) {
        if (typeof localStorage === 'undefined') return null
        return localStorage.getItem(key)
    },
    async set(key, value) {
        if (typeof localStorage !== 'undefined') localStorage.setItem(key, value)
    },
    async remove(key) {
        if (typeof localStorage !== 'undefined') localStorage.removeItem(key)
    },
}

let singleton: TokenStorage | null = null

export function useTokenStorage(): TokenStorage {
    if (!singleton) singleton = createTokenStorage(localStorageBackend)

    return singleton
}
