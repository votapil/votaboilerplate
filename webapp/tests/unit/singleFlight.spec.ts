import { describe, expect, it, vi } from 'vitest'
import { singleFlight } from '~/utils/singleFlight'

describe('singleFlight', () => {
    it('shares one run between concurrent calls (the double-tap case)', async () => {
        const spy = vi.fn(async () => 'saved')
        const guarded = singleFlight(spy)

        const [a, b] = await Promise.all([guarded(), guarded()])

        expect(spy).toHaveBeenCalledTimes(1)
        expect(a).toBe('saved')
        expect(b).toBe('saved')
    })

    it('runs fresh once the previous call has settled', async () => {
        const spy = vi.fn(async () => 1)
        const guarded = singleFlight(spy)

        await guarded()
        await guarded()

        expect(spy).toHaveBeenCalledTimes(2)
    })

    it('releases the slot after a rejection, so a retry is possible', async () => {
        const spy = vi.fn(async () => {
            throw new Error('nope')
        })
        const guarded = singleFlight(spy)

        await expect(guarded()).rejects.toThrow('nope')
        await expect(guarded()).rejects.toThrow('nope')

        expect(spy).toHaveBeenCalledTimes(2)
    })

    it('passes arguments through from whichever call started the run', async () => {
        const guarded = singleFlight(async (a: number, b: number) => a + b)

        await expect(guarded(2, 3)).resolves.toBe(5)
    })
})
