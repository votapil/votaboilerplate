/**
 * Wrap an async function so concurrent invocations share ONE in-flight run:
 * while a call is pending, further calls return the same promise instead of
 * starting a new one. After it settles, the next call runs fresh.
 *
 * Twelve lines that close a whole class of bugs on touch devices: a double tap
 * on "Save" fires the submit handler twice and creates two records. Wrap every
 * mutating submit handler with this instead of hand-rolling a `saving` flag.
 */
export function singleFlight<A extends unknown[], R>(
    fn: (...args: A) => Promise<R>,
): (...args: A) => Promise<R> {
    let inFlight: Promise<R> | null = null

    return (...args: A): Promise<R> => {
        if (inFlight) return inFlight
        inFlight = Promise.resolve(fn(...args)).finally(() => {
            inFlight = null
        })
        return inFlight
    }
}
