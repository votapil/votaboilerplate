import { readFileSync, readdirSync } from 'node:fs'
import { fileURLToPath } from 'node:url'
import { describe, expect, it } from 'vitest'

/**
 * A HARD limit on single-file component size.
 *
 * "Do not write monolithic components" written in a style guide changes nothing: the same
 * guidance, in the same repository, coexisted with a 2344-line page and four more over a
 * thousand. A rule without a number and a check is a rule nobody can break.
 *
 * 400 lines is the number. It is not a style preference — it is roughly the point past which a
 * component stops fitting in one head, review turns into skimming, and two people can no longer
 * work on the same screen. Hitting it is a signal to extract: a child component, a composable
 * for the logic, or a `<script>` block moved into `app/utils` where it can be unit-tested.
 *
 * If you are here because the test failed: split the file. Raising MAX_LINES is a decision
 * about the whole codebase, not about your one screen, and it needs to be argued for in the
 * commit message.
 */
const MAX_LINES = 400

const root = fileURLToPath(new URL('../../app', import.meta.url))

const vueFiles = (dir: string): string[] =>
    readdirSync(dir, { withFileTypes: true }).flatMap((entry) => {
        const path = `${dir}/${entry.name}`
        if (entry.isDirectory()) return vueFiles(path)
        return entry.name.endsWith('.vue') ? [path] : []
    })

describe('single-file components', () => {
    it(`are each under ${MAX_LINES} lines`, () => {
        const oversized = vueFiles(root)
            .map(path => ({
                file: path.slice(root.length + 1),
                lines: readFileSync(path, 'utf-8').split('\n').length,
            }))
            .filter(entry => entry.lines > MAX_LINES)
            .sort((a, b) => b.lines - a.lines)

        expect(oversized).toEqual([])
    })
})
