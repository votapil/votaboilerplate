<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    /** Hash once and reuse: bcrypt is deliberately slow, and factories are hot. */
    protected static ?string $password = null;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => $this->uniqueEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes): array => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Roles are rows, not attributes, so they can only be attached after the
     * user exists. findOrCreate keeps the state usable in tests that did not
     * seed the role table.
     */
    public function admin(): static
    {
        return $this->afterCreating(
            fn (User $user) => $user->assignRole(Role::findOrCreate(User::ROLE_ADMIN, 'web'))
        );
    }

    public function withSettings(): static
    {
        return $this->has(UserSetting::factory(), 'settings');
    }

    /**
     * A value under a UNIQUE index needs two guards, not one.
     *
     * fake()->unique() only remembers what THIS factory instance handed out: it
     * knows nothing about rows already in the database, nothing about the other
     * parallel test worker writing to the same schema, and it forgets
     * everything between processes. Relying on it alone produces a duplicate
     * key violation that reproduces roughly one run in twenty — the most
     * expensive kind of flake, because it looks like an unrelated failure.
     */
    private function uniqueEmail(): string
    {
        do {
            $email = mb_strtolower(fake()->unique()->safeEmail());
        } while (User::query()->where('email', $email)->exists());

        return $email;
    }
}
