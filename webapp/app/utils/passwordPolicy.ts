/**
 * What a NEW password has to satisfy — deliberately the SAME definition the API enforces.
 *
 * The character classes mirror Laravel's `Password::min(8)->letters()->numbers()->symbols()`
 * one to one (`\p{L}`, `\p{N}`, and `\p{Z}\p{S}\p{P}` for symbols), so a tick on the screen
 * can never go green where the server says no — and never refuse what the server would take.
 *
 * "Symbol" is the whole Unicode punctuation/symbol range on purpose, NOT a hand-picked list
 * like `!@#$%^&*`. Every password manager emits `=` or `-`; a narrow list turns a perfectly
 * strong generated password into an error the user cannot explain. A space counts too, which
 * lets a passphrase satisfy the rule without hunting for a symbol key.
 *
 * If the backend rule ever changes, change it HERE in the same commit.
 */
export interface PasswordChecks {
    /** at least MIN_PASSWORD_LENGTH characters (counted as characters, not bytes) */
    length: boolean
    letter: boolean
    digit: boolean
    symbol: boolean
}

const LETTER = /\p{L}/u
const DIGIT = /\p{N}/u
const SYMBOL = /[\p{Z}\p{S}\p{P}]/u

export const MIN_PASSWORD_LENGTH = 8

export function checkPassword(value: string | null | undefined): PasswordChecks {
    const v = value ?? ''

    return {
        // Spread first: `.length` on a string counts UTF-16 units, so an emoji would count as 2.
        length: [...v].length >= MIN_PASSWORD_LENGTH,
        letter: LETTER.test(v),
        digit: DIGIT.test(v),
        symbol: SYMBOL.test(v),
    }
}

export function passwordMeetsPolicy(value: string | null | undefined): boolean {
    return Object.values(checkPassword(value)).every(Boolean)
}
