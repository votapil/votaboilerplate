<?php

use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Self-service account deletion. Every mobile store now requires an in-app path
 * to it, so the endpoint ships with the template rather than being bolted on at
 * submission time. What matters: it removes the caller's own account, takes its
 * tokens with it, and cannot be pointed at anybody else.
 */
test('an authenticated user can delete their own account', function () {
    $user = User::factory()->create();
    $token = $user->createToken('mobile')->plainTextToken;

    $this->withToken($token)
        ->deleteJson(route('auth.delete-account'))
        ->assertSuccessful();

    expect(User::find($user->id))->toBeNull()
        ->and(PersonalAccessToken::count())->toBe(0);

    // See AuthenticationTest: RequestGuard caches the resolved user for the
    // lifetime of the container, so a second authenticated call in the same test
    // never re-checks the token unless the guards are forgotten first.
    $this->app['auth']->forgetGuards();

    $this->withToken($token)->getJson(route('auth.me'))->assertUnauthorized();
});

test('deleting an account leaves other accounts alone', function () {
    $bystander = User::factory()->create();
    $user = User::factory()->create();

    $this->withToken($user->createToken('mobile')->plainTextToken)
        ->deleteJson(route('auth.delete-account'))
        ->assertSuccessful();

    expect(User::find($bystander->id))->not->toBeNull();
});

test('a guest cannot delete an account', function () {
    $user = User::factory()->create();

    $this->deleteJson(route('auth.delete-account'))->assertUnauthorized();

    expect(User::find($user->id))->not->toBeNull();
});
