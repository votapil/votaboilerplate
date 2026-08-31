import type { ThemeInstance } from 'vuetify'

/**
 * Single source of truth for applying a theme preference to Vuetify.
 *
 * The Vuetify theme instance is passed IN. That is not style, it is a constraint: Vuetify's
 * own `useTheme()` needs a component setup context and throws ("must be called from inside a
 * setup function") when a plugin calls it. So the plugin gets the instance from the
 * `vuetify:ready` hook and hands it here, while a settings page passes `useTheme()`. One
 * implementation serves both, and the logic below is testable on its own.
 *
 * 'system' is resolved against the OS colour scheme AND tracked live, so the app follows a
 * scheduled dark mode while 'system' stays selected.
 */
export type ThemePreference = 'system' | 'light' | 'dark'

export const THEME_STORAGE_KEY = 'app_theme'

const prefersDark = (): boolean =>
    typeof window !== 'undefined'
    && window.matchMedia?.('(prefers-color-scheme: dark)').matches === true

const resolve = (pref: ThemePreference): 'light' | 'dark' =>
    pref === 'system' ? (prefersDark() ? 'dark' : 'light') : pref

export function useAppTheme(theme: ThemeInstance) {
    // Kept so only ever ONE OS listener is attached, however often applyTheme runs.
    let mql: MediaQueryList | null = null
    let listener: (() => void) | null = null

    const detach = () => {
        if (mql && listener) mql.removeEventListener('change', listener)
        mql = null
        listener = null
    }

    const applyTheme = (pref: ThemePreference) => {
        if (typeof window === 'undefined') return

        theme.change(resolve(pref))
        localStorage.setItem(THEME_STORAGE_KEY, pref)

        detach()
        if (pref === 'system') {
            mql = window.matchMedia('(prefers-color-scheme: dark)')
            listener = () => theme.change(prefersDark() ? 'dark' : 'light')
            mql.addEventListener('change', listener)
        }
    }

    const getStoredPreference = (): ThemePreference => {
        if (typeof window === 'undefined') return 'system'

        const stored = localStorage.getItem(THEME_STORAGE_KEY)
        return stored === 'light' || stored === 'dark' || stored === 'system' ? stored : 'system'
    }

    return { applyTheme, getStoredPreference }
}
