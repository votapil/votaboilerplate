/**
 * `definePageMeta({ permission })` — read by middleware/auth.global.ts.
 *
 * Declaring it here is what keeps the mechanism alive: without the augmentation the key is a
 * plain unknown field, typos compile, and the gate quietly never fires.
 */
declare module '#app' {
    interface PageMeta {
        permission?: string
    }
}

declare module 'vue-router' {
    interface RouteMeta {
        permission?: string
    }
}

export {}
