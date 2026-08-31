<?php

use Filament\Resources\Resource;

/**
 * Generated Filament resources all arrive with the same placeholder icon and no
 * navigation group, so the sidebar degrades into one flat alphabetical list the
 * moment a project has more than a handful of entities. Grooming that back into
 * shape after the fact cost the donor project a dedicated feature spec.
 *
 * Two lines of upkeep per resource, enforced from the first one.
 */
test('every resource in the sidebar has a real icon and a group', function () {
    $files = glob(app_path('Filament/Resources/*Resource.php')) ?: [];

    if ($files === []) {
        $this->markTestSkipped('No Filament resources yet — this guard arms itself with the first one.');
    }

    foreach ($files as $file) {
        /** @var class-string<Filament\Resources\Resource> $class */
        $class = 'App\\Filament\\Resources\\'.basename($file, '.php');

        if (! $class::shouldRegisterNavigation()) {
            continue; // deliberately hidden from the menu
        }

        expect($class::getNavigationIcon())->not->toBe(
            'heroicon-o-rectangle-stack',
            "{$class} still carries the scaffolding placeholder icon"
        );

        expect($class::getNavigationGroup())->not->toBeNull(
            "{$class} belongs to no navigation group and will land at the bottom of a flat list"
        );
    }
});
