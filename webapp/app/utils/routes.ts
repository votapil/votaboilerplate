/**
 * Pages reachable WITHOUT a session. The auth guard skips these paths.
 *
 * Keep it exhaustive: a page missing from this list becomes unreachable in a way that looks
 * like a redirect loop (guard sends you to /login, /login is guarded, repeat).
 */
export const PUBLIC_PAGES: string[] = [
    '/login',
    '/register',
]
