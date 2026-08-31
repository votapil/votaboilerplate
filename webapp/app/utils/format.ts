/**
 * Presentation formatters. Pure — no Nuxt, no Vuetify, no store — so they are unit-tested
 * directly (tests/unit/format.spec.ts) without booting a runtime.
 *
 * RULE: the moment the same value is formatted on BOTH sides of the API, the two sides drift.
 * If a formatter here mirrors a backend one, the spec file must say so in its description and
 * assert the backend's exact expected output. A mirror without a test is not a mirror.
 */
import { intlLocale } from './intlLocale'

export function formatNumber(
    value: number,
    locale?: string | null,
    options: Intl.NumberFormatOptions = {},
): string {
    if (!Number.isFinite(value)) return ''

    return new Intl.NumberFormat(intlLocale(locale), options).format(value)
}

export function formatDate(
    value: Date | string | number | null | undefined,
    locale?: string | null,
    options: Intl.DateTimeFormatOptions = { dateStyle: 'medium' },
): string {
    if (value === null || value === undefined || value === '') return ''

    const date = value instanceof Date ? value : new Date(value)
    if (Number.isNaN(date.getTime())) return ''

    return new Intl.DateTimeFormat(intlLocale(locale), options).format(date)
}

/** Trim a string to `max` characters, appending an ellipsis when it was cut. */
export function truncate(value: string, max: number): string {
    const chars = [...(value ?? '')]

    return chars.length <= max ? (value ?? '') : `${chars.slice(0, max).join('')}…`
}
