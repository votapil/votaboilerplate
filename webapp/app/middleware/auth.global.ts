/**
 * Global route guard: authentication, then per-page authorisation.
 *
 * The permission branch is the part that is easy to write and then never use. It exists so a
 * gated page is gated on DIRECT NAVIGATION too, not only by hiding the menu item — a hidden
 * link is not a guard, the URL is still typeable. Declare it on the page:
 *
 *     definePageMeta({ permission: PERMISSIONS.adminAccess })
 *
 * `app/pages/admin-area.vue` does exactly that; copy it. The check is local (permissions came
 * with /auth/me) and costs no request. The API enforces the same permission anyway — this only
 * saves the user from landing on a screen that would 403.
 */
export default defineNuxtRouteMiddleware(async (to) => {
    // The token lives in localStorage; there is nothing to decide server-side.
    if (import.meta.server) return

    if (PUBLIC_PAGES.includes(to.path)) return

    const auth = useAuthStore()
    if (!auth.token) return navigateTo('/login')

    // First navigation of the session: the token is known, the user is not yet.
    if (!auth.user) await auth.fetchMe()
    if (!auth.user) return navigateTo('/login')

    const required = to.meta.permission
    if (required && !auth.can(required)) return navigateTo('/')
})
