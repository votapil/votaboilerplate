<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * The first administrator, for local development only.
 *
 * Two guards, both learned the hard way:
 *
 *  1. It refuses to run outside local/testing. Deploys run `db:seed --force` on
 *     every release, and a seeder that touches an admin account there resets
 *     that account's password on every single deploy.
 *
 *  2. There is no password in this file, and none is read from anywhere it
 *     could be committed. Credentials in a repository are published
 *     credentials. The password is generated once and printed once; change it
 *     from the panel, or delete the row and re-seed to get a new one.
 */
class AdminUserSeeder extends Seeder
{
    private const EMAIL = 'admin@example.test';

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $user = User::findByEmail(self::EMAIL);

        if ($user === null) {
            $password = Str::password(16);

            $user = User::create([
                'name' => 'Administrator',
                'email' => self::EMAIL,
                'password' => $password,
            ]);

            $user->settings()->create([]);

            $this->command?->warn('Admin account created: '.self::EMAIL.' / '.$password);
            $this->command?->warn('This password is shown once and is not stored anywhere else.');
        }

        // Re-assigning is harmless and repairs an account that lost the role.
        $user->assignRole(Role::findOrCreate(User::ROLE_ADMIN, 'web'));
    }
}
