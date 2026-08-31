/**
 * Warm up the session cache BEFORE anything else runs.
 *
 * The `00.` prefix is load-bearing: Nuxt executes plugins in filename order, and the auth
 * store reads the token SYNCHRONOUSLY inside `setup()`. If the storage backend is async (it is
 * behind an async interface so it can be swapped for a native key store), the read has to have
 * finished before the store, the router or any other plugin touches it — otherwise the very
 * first navigation of a logged-in user bounces to /login.
 */
export default defineNuxtPlugin(async () => {
    await useTokenStorage().warmUp()
})
