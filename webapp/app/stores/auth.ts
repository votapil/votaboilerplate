/**
 * Session + authorisation state.
 *
 * Permissions arrive with the user in ONE request (`GET /api/v1/auth/me`), so `can()` is a
 * local Set lookup: rendering a menu of twenty gated items costs zero extra requests. Never
 * add an endpoint that answers "may I?" — add the permission to the /me payload instead.
 *
 * `can()` / `hasRole()` are convenience, not security. The API enforces the same permission
 * server-side; the frontend only decides what to show and where to let the user navigate.
 */
export interface AuthUser {
    id: number
    name: string
    email: string
    roles: string[]
    permissions: string[]
    settings?: {
        locale?: string
        theme?: string
    }
}

export const useAuthStore = defineStore('auth', () => {
    const api = useApi()

    const user = ref<AuthUser | null>(null)
    const token = ref<string | null>(null)
    const loading = ref(false)

    // Synchronous read — the cache was warmed by plugins/00.session.client.ts before this runs.
    if (import.meta.client) token.value = useTokenStorage().getToken()

    const isAuthenticated = computed(() => !!token.value && !!user.value)

    const permissionSet = computed(() => new Set(user.value?.permissions ?? []))
    const roleSet = computed(() => new Set(user.value?.roles ?? []))

    const can = (permission: string): boolean => permissionSet.value.has(permission)
    const hasRole = (role: string): boolean => roleSet.value.has(role)
    const hasAnyRole = (...roles: string[]): boolean => roles.some(hasRole)

    const setToken = (value: string) => {
        token.value = value
        if (import.meta.client) useTokenStorage().setToken(value)
    }

    const clearToken = () => {
        token.value = null
        if (import.meta.client) useTokenStorage().clearToken()
    }

    const fetchMe = async (): Promise<void> => {
        loading.value = true
        try {
            const response = await api.apiGet<{ data?: AuthUser } & AuthUser>('/api/v1/auth/me')
            user.value = (response.data ?? response) as AuthUser
        } catch {
            // A stale token 401s; useApi has already cleared it and redirected to /login.
            user.value = null
        } finally {
            loading.value = false
        }
    }

    /** Read the token out of whatever shape the auth endpoints answer with. */
    const takeToken = (response: unknown): string | null => {
        const body = response as { token?: string, data?: { token?: string } } | null
        return body?.token ?? body?.data?.token ?? null
    }

    const login = async (email: string, password: string): Promise<void> => {
        const response = await api.apiPost<unknown>('/api/v1/auth/login', { email, password })
        const issued = takeToken(response)
        if (!issued) throw new Error('Login response carried no token')

        setToken(issued)
        await fetchMe()
    }

    const register = async (payload: {
        name: string
        email: string
        password: string
        password_confirmation: string
    }): Promise<void> => {
        const response = await api.apiPost<unknown>('/api/v1/auth/register', payload)
        const issued = takeToken(response)
        if (!issued) throw new Error('Register response carried no token')

        setToken(issued)
        await fetchMe()
    }

    const logout = async (): Promise<void> => {
        try {
            await api.apiPost('/api/v1/auth/logout')
        } catch {
            // The local session is dropped either way: a user who tapped "log out" must end up
            // logged out even when the request fails.
        }
        user.value = null
        clearToken()
        await navigateTo('/login')
    }

    return {
        user,
        token,
        loading,
        isAuthenticated,
        can,
        hasRole,
        hasAnyRole,
        setToken,
        clearToken,
        fetchMe,
        login,
        register,
        logout,
    }
})
