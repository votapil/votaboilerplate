<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\PermissionName;
use App\Models\User;

/**
 * The only source of DEFAULTS for the access matrix.
 *
 * Read this distinction carefully, it is the whole design:
 *
 *   - This file says which roles receive a permission AT THE MOMENT THE
 *     PERMISSION IS FIRST CREATED. It is a birth certificate, not a policy.
 *   - The runtime truth of "role x permission" is the database, edited from the
 *     admin panel. Database\Seeders\RolesAndPermissionsSeeder is strictly
 *     additive and never re-syncs an existing link, so `db:seed --force` on
 *     every deploy cannot undo what an administrator changed by hand.
 *
 * Adding a permission: add a case to App\Enums\PermissionName, add an entry
 * here, deploy (the seeder creates it), then wire at least one check.
 *
 * Octane-safe: constant data only, no mutable statics.
 *
 * Descriptions are developer/administrator facing (they show up in the admin
 * panel and in the generated permission reference), so they are not translated.
 */
final class PermissionRegistry
{
    /**
     * Every role in the system. `admin` implicitly receives every permission
     * (see defaultRolesFor) so the application can never be locked out of its
     * own access screen.
     *
     * @var list<string>
     */
    public const ROLES = [
        User::ROLE_ADMIN,
        User::ROLE_USER,
    ];

    /**
     * permission => [description, roles that get it on creation].
     *
     * @return array<string, array{description: string, roles: list<string>}>
     */
    public static function all(): array
    {
        return [
            PermissionName::AdminAccess->value => [
                'description' => 'Sign in to the admin panel',
                'roles' => [User::ROLE_ADMIN],
            ],
            PermissionName::AdminUsers->value => [
                'description' => 'User accounts, and the roles and permissions they hold',
                'roles' => [User::ROLE_ADMIN],
            ],
            PermissionName::AdminSystem->value => [
                'description' => 'Operational dashboards, including the queue monitor',
                'roles' => [User::ROLE_ADMIN],
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function defaultRolesFor(string $permission): array
    {
        $roles = self::all()[$permission]['roles'] ?? [];

        return in_array(User::ROLE_ADMIN, $roles, true)
            ? $roles
            : [User::ROLE_ADMIN, ...$roles];
    }

    public static function description(string $permission): string
    {
        return self::all()[$permission]['description'] ?? $permission;
    }

    /**
     * permission => description, ready for a checkbox list in the admin panel.
     *
     * @return array<string, string>
     */
    public static function descriptions(): array
    {
        return array_map(static fn (array $definition): string => $definition['description'], self::all());
    }
}
