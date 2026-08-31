<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

/**
 * Create an account: the user row, its settings row and its default role.
 *
 * This class exists as much as an example as a feature. "Keep controllers thin"
 * is a rule every project writes down and most projects break, because the rule
 * is prose while the generator hands you a controller with a body. An Action
 * with one public handle() is the shape the rule actually needs: when the next
 * step is added (send a welcome mail, provision a workspace) it has an obvious
 * home that is not the controller.
 */
final class RegisterUserAction
{
    /**
     * @param  array{name: string, email: string, password: string, locale?: string|null}  $attributes
     */
    public function handle(array $attributes): User
    {
        return DB::transaction(function () use ($attributes): User {
            $user = User::create([
                'name' => $attributes['name'],
                // The model lowercases the address and the 'hashed' cast hashes
                // the password; do neither here or you hash twice.
                'email' => $attributes['email'],
                'password' => $attributes['password'],
            ]);

            // No explicit choice? Inherit the language this request arrived in.
            // SetUserLocale has already negotiated it from Accept-Language, so a
            // Russian-speaking visitor gets a Russian account without asking.
            $user->settings()->create([
                'locale' => $attributes['locale'] ?? app()->getLocale(),
            ]);

            // findOrCreate, not assignRole('user'): a fresh database that has
            // not been seeded yet would otherwise turn every registration into
            // a 500. The role's PERMISSIONS still come from the seeder.
            $user->assignRole(Role::findOrCreate(User::ROLE_USER, 'web'));

            return $user;
        });
    }
}
