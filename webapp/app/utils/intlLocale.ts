/**
 * Turns an app locale code into a BCP-47 tag fit for `Intl` / `toLocale*` calls.
 *
 * This exists because a bare two-letter code is not always what `Intl` thinks it is.
 * The case that cost us: `sr` resolves to Serbian Cyrillic (`sr-Cyrl-RS`), so dates came
 * out in Cyrillic next to a Latin-script interface. The script has to be spelled out.
 *
 * Unknown codes pass through untouched rather than falling back to the default locale:
 * a language added later must format in its own language, not silently in someone else's.
 */
const TAGS: Record<string, string> = {
    en: 'en-US',
    ru: 'ru-RU',
}

export const DEFAULT_INTL_LOCALE = 'en-US'

export function intlLocale(locale?: string | null): string {
    const code = (locale ?? '').trim()
    if (!code) return DEFAULT_INTL_LOCALE

    return TAGS[code.slice(0, 2).toLowerCase()] ?? code
}
