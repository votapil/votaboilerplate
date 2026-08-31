import { describe, expect, it, vi } from 'vitest'
import { TOKEN_KEY, type TokenBackend, createTokenStorage } from '~/utils/tokenStorage'

/**
 * The core takes its backend as an argument, so the whole thing is testable with a fake and
 * without a browser, a Nuxt runtime or a mocked global. That is the reason for the split — the
 * wrapper (composables/useTokenStorage.ts) has nothing left in it worth testing.
 */
const fakeBackend = (initial: Record<string, string> = {}) => {
    const store = new Map(Object.entries(initial))
    const calls: string[] = []

    const backend: TokenBackend = {
        async get(key) {
            calls.push(`get:${key}`)
            return store.get(key) ?? null
        },
        async set(key, value) {
            calls.push(`set:${key}`)
            store.set(key, value)
        },
        async remove(key) {
            calls.push(`remove:${key}`)
            store.delete(key)
        },
    }

    return { backend, store, calls }
}

describe('createTokenStorage', () => {
    it('reads nothing before warm-up, then serves the stored token synchronously', async () => {
        const { backend } = fakeBackend({ [TOKEN_KEY]: 'stored-token' })
        const storage = createTokenStorage(backend)

        // This is the whole reason plugins/00.session.client.ts exists and runs first: before
        // warm-up the cache is empty, and the auth store would treat the user as signed out.
        expect(storage.getToken()).toBeNull()

        await storage.warmUp()

        expect(storage.getToken()).toBe('stored-token')
    })

    it('exposes a written token immediately, before the backend has caught up', async () => {
        const { backend, store } = fakeBackend()
        const storage = createTokenStorage(backend)

        storage.setToken('fresh')

        expect(storage.getToken()).toBe('fresh')
        expect(store.get(TOKEN_KEY)).toBeUndefined()

        await storage.flush()

        expect(store.get(TOKEN_KEY)).toBe('fresh')
    })

    it('applies queued writes in order, so the last write wins', async () => {
        const { backend, store, calls } = fakeBackend()
        const storage = createTokenStorage(backend)

        storage.setToken('first')
        storage.setToken('second')
        await storage.flush()

        expect(calls).toEqual([`set:${TOKEN_KEY}`, `set:${TOKEN_KEY}`])
        expect(store.get(TOKEN_KEY)).toBe('second')
    })

    it('clears the cache immediately and the backend right after', async () => {
        const { backend, store } = fakeBackend({ [TOKEN_KEY]: 'stored' })
        const storage = createTokenStorage(backend)
        await storage.warmUp()

        storage.clearToken()

        expect(storage.getToken()).toBeNull()

        await storage.flush()

        expect(store.has(TOKEN_KEY)).toBe(false)
    })

    it('keeps accepting writes after one of them fails', async () => {
        const { backend } = fakeBackend()
        const set = vi.spyOn(backend, 'set')
            .mockRejectedValueOnce(new Error('quota exceeded'))
        const storage = createTokenStorage(backend)

        storage.setToken('one')
        storage.setToken('two')
        await storage.flush()

        // A rejected write must not stall the queue — a failed persist would otherwise mean
        // every later token silently never reaches storage.
        expect(set).toHaveBeenCalledTimes(2)
        expect(storage.getToken()).toBe('two')
    })
})
