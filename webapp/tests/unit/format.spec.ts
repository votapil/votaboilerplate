import { describe, expect, it } from 'vitest'
import { DEFAULT_INTL_LOCALE, intlLocale } from '~/utils/intlLocale'
import { formatDate, formatNumber, truncate } from '~/utils/format'

describe('intlLocale', () => {
    it('expands app codes to BCP-47 tags', () => {
        expect(intlLocale('en')).toBe('en-US')
        expect(intlLocale('ru')).toBe('ru-RU')
    })

    it('passes unknown codes through instead of substituting a default', () => {
        // A language added later must format in its own language, not silently in English.
        expect(intlLocale('de-AT')).toBe('de-AT')
    })

    it('falls back only when there is no code at all', () => {
        expect(intlLocale('')).toBe(DEFAULT_INTL_LOCALE)
        expect(intlLocale(null)).toBe(DEFAULT_INTL_LOCALE)
        expect(intlLocale(undefined)).toBe(DEFAULT_INTL_LOCALE)
    })
})

describe('formatNumber', () => {
    // ICU quietly changes WHICH invisible space it groups with (U+00A0 vs U+202F) between
    // Node releases. Normalising them keeps the assertion about grouping and the decimal
    // comma — the things that actually matter — instead of about a byte.
    const spaces = (value: string) => value.replace(/[\u00A0\u202F\u2009]/g, ' ')

    it('uses the separators of the locale', () => {
        expect(spaces(formatNumber(1234567.5, 'en', { minimumFractionDigits: 2 })))
            .toBe('1,234,567.50')
        expect(spaces(formatNumber(1234567.5, 'ru', { minimumFractionDigits: 2 })))
            .toBe('1 234 567,50')
    })

    it('returns an empty string for values that cannot be shown', () => {
        expect(formatNumber(Number.NaN, 'en')).toBe('')
        expect(formatNumber(Number.POSITIVE_INFINITY, 'en')).toBe('')
    })
})

describe('formatDate', () => {
    it('formats in the language of the locale', () => {
        const date = new Date(Date.UTC(2026, 7, 23))

        expect(formatDate(date, 'en', { dateStyle: 'medium', timeZone: 'UTC' })).toBe('Aug 23, 2026')
        expect(formatDate(date, 'ru', { dateStyle: 'medium', timeZone: 'UTC' })).toBe('23 авг. 2026 г.')
    })

    it('accepts an ISO string as well as a Date', () => {
        expect(formatDate('2026-08-23T00:00:00Z', 'en', { dateStyle: 'short', timeZone: 'UTC' }))
            .toBe('8/23/26')
    })

    it('renders nothing rather than "Invalid Date"', () => {
        expect(formatDate(null, 'en')).toBe('')
        expect(formatDate('', 'en')).toBe('')
        expect(formatDate('not a date', 'en')).toBe('')
    })
})

describe('truncate', () => {
    it('cuts by characters and marks the cut', () => {
        expect(truncate('abcdefgh', 4)).toBe('abcd…')
    })

    it('leaves a short string untouched', () => {
        expect(truncate('abcd', 4)).toBe('abcd')
    })
})
