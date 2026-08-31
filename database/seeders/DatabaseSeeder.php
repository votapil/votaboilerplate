<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Runs on every deploy (`db:seed --force`), so everything called from here has
 * to be idempotent and safe against production data.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            // No-op outside local/testing, see the class for why.
            AdminUserSeeder::class,
        ]);
    }
}
