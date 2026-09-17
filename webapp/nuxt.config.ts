// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  // Token-authenticated SPA served by Laravel's catch-all route: there is no server to render
  // on, and the session token lives in localStorage, which a server render cannot see.
  ssr: false,

  compatibilityDate: '2026-09-17',
  devtools: { enabled: true },

  app: {
    head: {
      meta: [
        { name: 'viewport', content: 'width=device-width, initial-scale=1, viewport-fit=cover' },
      ],
    },
  },

  runtimeConfig: {
    public: {
      // Empty = same origin (Laravel serves the built SPA). Set NUXT_PUBLIC_API_BASE to an
      // absolute URL when the frontend is hosted apart from the API.
      apiBase: '',
      // Kill switch for app/plugins/error-reporter.client.ts (NUXT_PUBLIC_CLIENT_ERROR_REPORTING=false).
      clientErrorReporting: true,
    },
  },

  modules: [
    '@pinia/nuxt',
    'vuetify-nuxt-module',
    '@nuxtjs/i18n',
  ],

  css: [
    '~/assets/css/app.css',
  ],

  i18n: {
    // Locale files live in i18n/locales/. Keys must exist in EVERY file — tests/unit/i18nParity.spec.ts
    // fails the build gate on a key added to one language and forgotten in another.
    locales: [
      { code: 'en', name: 'English', file: 'en.json' },
      { code: 'ru', name: 'Русский', file: 'ru.json' },
    ],
    langDir: 'locales',
    defaultLocale: 'en',
    // One URL per page, language kept in a cookie: the app is behind a login, so there is
    // nothing to gain from /ru/ prefixes and a lot of routing to lose.
    strategy: 'no_prefix',
    // Resolved relative to `i18n/`, NOT to the project root: the module's i18n directory is
    // the cwd for this lookup, so a config file sitting at webapp/i18n.config.ts is silently
    // skipped (dev logs a warning, production just runs without it).
    vueI18n: 'i18n.config.ts',
  },

  vuetify: {
    moduleOptions: {},
    vuetifyOptions: {
      // The ONE breakpoint that decides bottom-bar vs. rail (layouts/default.vue reads it via
      // useDisplay()). Never pass a local mobileBreakpoint in a component — two thresholds in
      // one app means two components disagree about which layout is showing.
      display: {
        mobileBreakpoint: 'md',
      },

      // ── Theme ────────────────────────────────────────────────────────────────────────
      // A full token set, on purpose. Vuetify's stock palette (#1976D2 on plain white) is
      // instantly recognisable as an untouched demo, and every screen that has to fix its own
      // colours re-invents the same greys slightly differently. Set the tokens once; screens
      // then use `surface`, `on-surface-variant`, `outline` and stop hard-coding hex values.
      // Every text pair below is >= 4.5:1 (WCAG AA) against its own background.
      theme: {
        // Boots light; plugins/theme.client.ts then applies the user's stored preference
        // ('system' resolves against the OS colour scheme).
        defaultTheme: 'light',
        themes: {
          light: {
            dark: false,
            colors: {
              'primary': '#2F4FCD',
              'on-primary': '#FFFFFF',
              'primary-container': '#E3E8FC',
              'on-primary-container': '#182B7E',
              'secondary': '#545B70',
              'on-secondary': '#FFFFFF',
              'secondary-container': '#E6E8F0',
              'on-secondary-container': '#2A2F3D',
              // Warm off-white canvas with white cards: the page reads as paper, cards as
              // objects on it — separation without a single shadow.
              'background': '#F7F7F5',
              'on-background': '#1D1F24',
              'surface': '#FFFFFF',
              'on-surface': '#1D1F24',
              'surface-variant': '#ECEDF2',
              'on-surface-variant': '#5B6068',
              'outline': '#C7C9D1',
              'outline-variant': '#E4E5E1',
              'success': '#2E7D4F',
              'info': '#2F4FCD',
              'warning': '#8A5A00',
              'error': '#C2362B',
              'on-error': '#FFFFFF',
            },
          },
          dark: {
            dark: true,
            colors: {
              'primary': '#9DB0FF',
              'on-primary': '#12204F',
              'primary-container': '#2B3768',
              'on-primary-container': '#DCE3FF',
              'secondary': '#AAB1C6',
              'on-secondary': '#232838',
              'secondary-container': '#2E3444',
              'on-secondary-container': '#DCE0EC',
              // Dark elevation by TONE, not by translucent white overlays: overlays stack and
              // turn every nested card into a slightly different grey.
              'background': '#121319',
              'on-background': '#E7E9F0',
              'surface': '#191B22',
              'on-surface': '#E7E9F0',
              'surface-variant': '#232631',
              'on-surface-variant': '#A6ACBD',
              'outline': '#3D4354',
              'outline-variant': '#2A2E3B',
              'success': '#7BC894',
              'info': '#9DB0FF',
              'warning': '#E5B567',
              'error': '#FF6B5E',
              'on-error': '#2A0D0A',
            },
          },
        },
      },

      // ── Component defaults ───────────────────────────────────────────────────────────
      // The reason a project ends up with hundreds of components each restating the same
      // props. Set the house style ONCE here; a component then only spells out a prop when it
      // genuinely deviates, and a deviation becomes visible in review instead of invisible.
      defaults: {
        VBtn: {
          rounded: 'lg',
          elevation: 0,
          // Sentence case, always. Upper-cased Cyrillic looks dated and clips accents.
          class: 'text-none',
        },
        VCard: {
          rounded: 'xl',
          flat: true,
          // Borders, not shadows: a hairline separates a card from the canvas without the
          // shadow soup that a scrolling list of elevated cards turns into.
          border: true,
        },
        VTextField: {
          variant: 'outlined',
          density: 'comfortable',
          rounded: 'lg',
          // 'auto' keeps the 22px message slot collapsed until there IS a message, so a form
          // does not carry a column of empty gaps.
          hideDetails: 'auto',
        },
        VSelect: {
          variant: 'outlined',
          density: 'comfortable',
          rounded: 'lg',
          hideDetails: 'auto',
        },
        VTextarea: {
          variant: 'outlined',
          density: 'comfortable',
          rounded: 'lg',
          hideDetails: 'auto',
        },
      },

      // NOTE: no `locale.messages` block here. vuetify-nuxt-module wires Vuetify's own
      // component strings to @nuxtjs/i18n automatically — hand-copying "close"/"loading" into
      // the build config only creates a second, always-incomplete translation file.
    },
  },

  vite: {
    server: {
      proxy: {
        // `npm run dev` on :3000 talking to the Laravel container on :81, so relative
        // /api/... URLs work in development exactly as they do in production.
        '/api': {
          target: process.env.VITE_API_PROXY_TARGET || 'http://localhost:81',
          changeOrigin: true,
        },
      },
    },
  },
})
