import { readFileSync, readdirSync } from 'node:fs'
import { fileURLToPath } from 'node:url'
import { describe, expect, it } from 'vitest'

/**
 * Locale files are the most-edited files in a project like this, and they drift the same way
 * every time: a key is added to the language you are testing in and forgotten in the other.
 * Nothing breaks — the user just sees `settings.theme_system` on their screen one day.
 *
 * Twenty lines of comparison turn that into a failing test the moment it happens.
 */
const dir = fileURLToPath(new URL('../../i18n/locales', import.meta.url))

const flatten = (value: unknown, prefix = ''): string[] => {
    if (value === null || typeof value !== 'object' || Array.isArray(value)) return [prefix]

    return Object.entries(value as Record<string, unknown>)
        .flatMap(([key, child]) => flatten(child, prefix ? `${prefix}.${key}` : key))
}

const load = (file: string) =>
    JSON.parse(readFileSync(`${dir}/${file}`, 'utf-8')) as Record<string, unknown>

const files = readdirSync(dir).filter(name => name.endsWith('.json')).sort()
const [reference, ...others] = files

describe('locale files', () => {
    it('ships more than one language', () => {
        expect(files.length).toBeGreaterThan(1)
    })

    const referenceKeys = flatten(load(reference!)).sort()

    it.each(others)(`%s has exactly the keys of ${reference}`, (file) => {
        const keys = flatten(load(file)).sort()

        expect({
            missing: referenceKeys.filter(key => !keys.includes(key)),
            extra: keys.filter(key => !referenceKeys.includes(key)),
        }).toEqual({ missing: [], extra: [] })
    })

    it.each(files)('%s has no empty translations', (file) => {
        const blanks: string[] = []
        const walk = (value: unknown, path: string) => {
            if (typeof value === 'string') {
                if (value.trim() === '') blanks.push(path)
                return
            }
            if (value && typeof value === 'object') {
                for (const [key, child] of Object.entries(value)) {
                    walk(child, path ? `${path}.${key}` : key)
                }
            }
        }
        walk(load(file), '')

        expect(blanks).toEqual([])
    })
})
