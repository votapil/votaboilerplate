import { describe, expect, it } from 'vitest'
import { MIN_PASSWORD_LENGTH, checkPassword, passwordMeetsPolicy } from '~/utils/passwordPolicy'

/**
 * MIRROR TEST — these expectations restate Laravel's
 * `Password::min(8)->letters()->numbers()->symbols()`. If the API rule changes, this file
 * fails first. That is the point: it is what stops the client checklist from going green on a
 * password the server rejects.
 */
describe('passwordPolicy', () => {
    it('requires eight characters, counted as characters', () => {
        expect(checkPassword('a1!aaaa').length).toBe(false)
        expect(checkPassword('a1!aaaaa').length).toBe(true)
        expect(MIN_PASSWORD_LENGTH).toBe(8)
    })

    it('counts astral characters as one, not two', () => {
        // '.length' on this string is 16 in UTF-16 units but 8 characters.
        expect(checkPassword('🙂🙂🙂🙂🙂🙂🙂🙂').length).toBe(true)
    })

    it('accepts letters and digits from any script', () => {
        const checks = checkPassword('пароль1!')
        expect(checks.letter).toBe(true)
        expect(checks.digit).toBe(true)
    })

    it.each(['=', '-', '_', '.', '+', '/', ' ', '~'])(
        'treats %j as a symbol, like the server does',
        (symbol) => {
            expect(checkPassword(`abcdefg1${symbol}`).symbol).toBe(true)
        },
    )

    it('rejects a password missing any single class', () => {
        expect(passwordMeetsPolicy('abcdefgh')).toBe(false)   // no digit, no symbol
        expect(passwordMeetsPolicy('abcdefg1')).toBe(false)   // no symbol
        expect(passwordMeetsPolicy('abcdefg!')).toBe(false)   // no digit
        expect(passwordMeetsPolicy('1234567!')).toBe(false)   // no letter
        expect(passwordMeetsPolicy('abcdefg1!')).toBe(true)
    })

    it('treats null and undefined as empty rather than throwing', () => {
        expect(passwordMeetsPolicy(null)).toBe(false)
        expect(passwordMeetsPolicy(undefined)).toBe(false)
    })
})
