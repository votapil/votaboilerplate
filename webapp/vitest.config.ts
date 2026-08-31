import { fileURLToPath } from 'node:url'
import { defineConfig } from 'vitest/config'

/**
 * A lean, Nuxt-free unit gate.
 *
 * This is the shape of testing that scales here: business logic lives in `app/utils/*` with no
 * Nuxt, Pinia or Vuetify import, so it runs in a plain node environment with no runtime to boot
 * — milliseconds per file, no mocking ceremony. Composables and stores stay thin enough that
 * there is little left in them to test.
 *
 * There is deliberately NO `exclude` list. A test that is written but excluded from the run is
 * worse than no test: it looks like coverage and asserts nothing. If a spec needs a Nuxt
 * runtime, add @nuxt/test-utils and run it — do not park it here switched off.
 */
export default defineConfig({
    resolve: {
        // Nuxt's `~` app alias, so a spec can import exactly the path the app imports.
        alias: { '~': fileURLToPath(new URL('./app', import.meta.url)) },
    },
    test: {
        include: ['tests/unit/**/*.spec.ts'],
        environment: 'node',
    },
})
