<?php

use App\Enums\PermissionName;

/**
 * The permission registry must describe reality.
 *
 * A permission that is declared, seeded, documented and shown as a checkbox in the
 * admin UI — but never checked anywhere in the code — is worse than no permission
 * at all: the matrix promises an access boundary that does not exist, and the
 * people reading the generated docs believe it. That happened in the project this
 * template comes from (a whole `admin.analytics` section was gated by nothing).
 *
 * So: every case of PermissionName has to appear in at least one enforcement point
 * — a policy, a `canAccess()`, a `permission:` middleware, a `can()` call.
 */
test('every declared permission is enforced somewhere in the code', function () {
    $cases = PermissionName::cases();

    expect($cases)->not->toBeEmpty();

    // The declaration sites themselves obviously mention every permission — they
    // are excluded so they cannot vouch for each other.
    $declarationSites = [
        app_path('Enums/PermissionName.php'),
        app_path('Support/PermissionRegistry.php'),
    ];

    $sources = collect(sourceFilesIn([app_path(), base_path('routes')]))
        ->reject(fn (string $file) => in_array($file, $declarationSites, true))
        ->map(fn (string $file) => (string) file_get_contents($file));

    foreach ($cases as $case) {
        $enforced = $sources->contains(
            fn (string $code) => str_contains($code, 'PermissionName::'.$case->name)
                || str_contains($code, "'".$case->value."'")
                || str_contains($code, '"'.$case->value.'"')
        );

        expect($enforced)->toBeTrue(
            "PermissionName::{$case->name} ('{$case->value}') is declared and seeded but never checked — "
            .'either gate something with it or delete it from the registry'
        );
    }
});

/**
 * @param  list<string>  $directories
 * @return list<string>
 */
function sourceFilesIn(array $directories): array
{
    $files = [];

    foreach ($directories as $directory) {
        if (! is_dir($directory)) {
            continue;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }
    }

    return $files;
}
