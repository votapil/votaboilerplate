<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Support\PermissionRegistry;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Creates roles and permissions. Idempotent and STRICTLY ADDITIVE.
 *
 * "Additive" is the whole point and it is easy to get wrong: default roles are
 * attached to a permission ONLY at the moment that permission is first created.
 * An existing permission is skipped entirely — its role links belong to
 * whoever edits the access matrix in the admin panel from then on.
 *
 * The naive version of this seeder calls syncRoles()/syncPermissions() and is
 * "safely idempotent" in the sense that it produces the same result every time:
 * it silently reverts every deliberate change an administrator made, on every
 * single deploy, because production runs `db:seed --force`.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (PermissionRegistry::ROLES as $role) {
            Role::findOrCreate($role, 'web');
        }

        foreach (array_keys(PermissionRegistry::all()) as $name) {
            $exists = Permission::query()
                ->where('name', $name)
                ->where('guard_name', 'web')
                ->exists();

            if ($exists) {
                continue;
            }

            $permission = Permission::create(['name' => $name, 'guard_name' => 'web']);

            foreach (PermissionRegistry::defaultRolesFor($name) as $role) {
                Role::findByName($role, 'web')->givePermissionTo($permission);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
