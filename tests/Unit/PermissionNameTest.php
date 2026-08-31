<?php

use App\Enums\PermissionName;

/**
 * Permission names are strings that travel: they are seeded into the database,
 * written into `permission:` middleware, generated into TypeScript for the client,
 * and printed into the permission docs. A typo in one of them does not fail — it
 * creates a second permission nobody holds, and the gate it guards closes for
 * everyone. So the shape of the name is worth pinning.
 */
test('every permission name follows the area.section convention', function () {
    foreach (PermissionName::cases() as $case) {
        expect($case->value)->toMatch(
            '/^[a-z][a-z0-9-]*(\.[a-z][a-z0-9-]*)+$/',
            "PermissionName::{$case->name} = '{$case->value}' — expected lowercase 'area.section[.action]'"
        );
    }
});

test('permission names are unique', function () {
    $values = array_map(fn (PermissionName $case) => $case->value, PermissionName::cases());

    expect($values)->toHaveCount(count(array_unique($values)));
});

test('a permission name can be resolved back from its string form', function () {
    // Middleware and policies pass these around as plain strings; the round trip
    // is what lets a gate be written as PermissionName::X->value and read back.
    foreach (PermissionName::cases() as $case) {
        expect(PermissionName::tryFrom($case->value))->toBe($case);
    }
});
