<?php

/**
 * Locale parity — the single most valuable structural guard in this suite.
 *
 * Translations drift one key at a time: a feature lands with `lang/en/billing.php`
 * updated and `lang/ru/billing.php` forgotten, nothing fails, and the Russian UI
 * quietly starts printing raw keys at users. In the project this template was
 * distilled from that pattern produced dozens of "fix i18n" commits — every one of
 * them a bug that shipped, because the parity check only ever existed inside
 * individual feature tests that listed their own keys by hand.
 *
 * This check is global on purpose: it covers keys that do not exist yet.
 */
test('every locale directory carries exactly the same keys', function () {
    $locales = collect(glob(lang_path('*'), GLOB_ONLYDIR) ?: [])
        ->map(fn (string $dir) => basename($dir))
        ->merge(collect(glob(lang_path('*.json')) ?: [])->map(fn (string $f) => basename($f, '.json')))
        ->unique()
        ->sort()
        ->values();

    // Guard against the guard: an empty lang/ would make everything below vacuous.
    expect($locales->all())->toContain('en')
        ->and($locales->all())->toContain('ru');

    $reference = array_keys(flattenedTranslations('en'));

    expect($reference)->not->toBeEmpty('lang/en is empty — nothing to keep in parity with');

    foreach ($locales->reject(fn (string $locale) => $locale === 'en') as $locale) {
        $keys = array_keys(flattenedTranslations($locale));

        $missing = array_diff($reference, $keys);
        $extra = array_diff($keys, $reference);

        expect($missing)->toBeEmpty(
            "lang/{$locale} is missing keys present in lang/en: ".implode(', ', array_slice($missing, 0, 20))
        );

        expect($extra)->toBeEmpty(
            "lang/{$locale} has keys that lang/en does not: ".implode(', ', array_slice($extra, 0, 20))
        );
    }
});

test('no translation resolves to an empty string', function () {
    // An empty value is worse than a missing one: it fails nothing and renders
    // nothing, so a button silently loses its label in one language only.
    foreach (glob(lang_path('*'), GLOB_ONLYDIR) ?: [] as $dir) {
        $locale = basename($dir);

        foreach (flattenedTranslations($locale) as $key => $value) {
            if (is_array($value)) {
                continue; // grouping node, e.g. validation.custom
            }

            expect(trim((string) $value))->not->toBe('', "lang/{$locale} → {$key} is empty");
        }
    }
});
