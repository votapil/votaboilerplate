// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  compatibilityDate: '2025-07-15',
  devtools: { enabled: true },
  modules: [
    '@pinia/nuxt',
    'vuetify-nuxt-module'
  ],
  vuetify: {
    moduleOptions: {
      /* vuetify-nuxt-module options */
    },
    vuetifyOptions: {
      /* vuetify options */
    }
  }
})
