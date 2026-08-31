import { describe, expect, it } from 'vitest'
import { errorMessage, fieldErrors } from '~/utils/apiErrors'

/**
 * The API error envelope is `{ message, errors?, code? }` and `$fetch` hands the parsed body
 * over on `error.data`. Both shapes are accepted here so a caller can pass the raw error.
 */
const fetchError = (body: unknown) => ({ data: body })

describe('fieldErrors', () => {
    it('flattens a 422 payload to one message per field', () => {
        const errors = fieldErrors(fetchError({
            message: 'The given data was invalid.',
            errors: {
                email: ['The email has already been taken.', 'Second message ignored.'],
                password: ['The password must be at least 8 characters.'],
            },
        }))

        expect(errors).toEqual({
            email: 'The email has already been taken.',
            password: 'The password must be at least 8 characters.',
        })
    })

    it('accepts a bare body as well as a thrown fetch error', () => {
        expect(fieldErrors({ errors: { name: ['Required.'] } })).toEqual({ name: 'Required.' })
    })

    it('returns nothing for responses without a validation payload', () => {
        expect(fieldErrors(fetchError({ message: 'Server error' }))).toEqual({})
        expect(fieldErrors(new Error('offline'))).toEqual({})
        expect(fieldErrors(null)).toEqual({})
    })

    it('skips fields whose message list is empty', () => {
        expect(fieldErrors({ errors: { email: [] } })).toEqual({})
    })
})

describe('errorMessage', () => {
    it('prefers the first field error over the generic 422 summary', () => {
        const message = errorMessage(fetchError({
            message: 'The given data was invalid.',
            errors: { email: ['That email is taken.'] },
        }), 'fallback')

        expect(message).toBe('That email is taken.')
    })

    it('uses the backend message when there is no validation payload', () => {
        // The backend already localised this string — that is why it wins over client copy.
        expect(errorMessage(fetchError({ message: 'Недостаточно прав.' }), 'fallback'))
            .toBe('Недостаточно прав.')
    })

    it('falls back when the response carries nothing usable', () => {
        expect(errorMessage(fetchError({}), 'fallback')).toBe('fallback')
        expect(errorMessage(fetchError({ message: '' }), 'fallback')).toBe('fallback')
        expect(errorMessage(undefined, 'fallback')).toBe('fallback')
    })
})
