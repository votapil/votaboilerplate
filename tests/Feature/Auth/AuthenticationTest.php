<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * The full token lifecycle of the API: register -> token -> /me -> logout.
 *
 * Assertions deliberately avoid pinning the JSON envelope (a resource can be
 * reshaped without a security change); what they do pin is the behaviour that
 * must never regress — a token is issued, it authenticates, logging out revokes
 * it, and a guest gets a 401 with the project's error envelope.
 */
test('a visitor can register and receives a working API token', function () {
    $response = $this->postJson(route('auth.register'), [
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.test',
        'password' => 'correct-horse-battery-staple-1',
        'password_confirmation' => 'correct-horse-battery-staple-1',
    ]);

    $response->assertSuccessful();

    $user = User::where('email', 'ada@example.test')->first();

    expect($user)->not->toBeNull()
        ->and(Hash::check('correct-horse-battery-staple-1', $user->password))->toBeTrue()
        ->and($user->password)->not->toBe('correct-horse-battery-staple-1');

    $token = sanctumTokenIn($response->json());

    expect($token)->not->toBeNull('registration must hand back a Sanctum token');

    $this->withToken($token)
        ->getJson(route('auth.me'))
        ->assertSuccessful()
        ->assertJsonFragment(['email' => 'ada@example.test']);
});

test('registration rejects an email that is already taken', function () {
    User::factory()->create(['email' => 'taken@example.test']);

    $this->postJson(route('auth.register'), [
        'name' => 'Impostor',
        'email' => 'taken@example.test',
        'password' => 'correct-horse-battery-staple-1',
        'password_confirmation' => 'correct-horse-battery-staple-1',
    ])->assertJsonValidationErrors('email');

    expect(User::where('email', 'taken@example.test')->count())->toBe(1);
});

test('a registered user can log in and use the returned token', function () {
    $user = User::factory()->create([
        'email' => 'grace@example.test',
        'password' => Hash::make('correct-horse-battery-staple-1'),
    ]);

    $response = $this->postJson(route('auth.login'), [
        'email' => 'grace@example.test',
        'password' => 'correct-horse-battery-staple-1',
    ])->assertSuccessful();

    $token = sanctumTokenIn($response->json());

    expect($token)->not->toBeNull('login must hand back a Sanctum token');

    $this->withToken($token)
        ->getJson(route('auth.me'))
        ->assertSuccessful()
        ->assertJsonFragment(['id' => $user->id]);
});

test('a wrong password issues no token', function () {
    User::factory()->create([
        'email' => 'grace@example.test',
        'password' => Hash::make('correct-horse-battery-staple-1'),
    ]);

    $response = $this->postJson(route('auth.login'), [
        'email' => 'grace@example.test',
        'password' => 'not-the-password',
    ]);

    // 422 (validation-style rejection) and 401 are both defensible; issuing a
    // token is not. The status is asserted loosely, the token strictly.
    expect($response->status())->toBeIn([401, 422])
        ->and(PersonalAccessToken::count())->toBe(0);
});

test('logging out revokes only the token that was used', function () {
    $user = User::factory()->create();
    $current = $user->createToken('current')->plainTextToken;
    $other = $user->createToken('other')->plainTextToken;

    $this->withToken($current)->postJson(route('auth.logout'))->assertSuccessful();

    expect(PersonalAccessToken::count())->toBe(1);

    // Sanctum's guard is a RequestGuard, and RequestGuard::setRequest() does NOT
    // clear the user it already resolved. The test process keeps one container
    // across all these calls, so without this line the revoked token still
    // "works" — the guard never looks at it a second time. Production is safe
    // (a fresh process per request; Octane flushes auth state between them), but
    // any test that authenticates twice in one function needs this.
    $this->app['auth']->forgetGuards();

    $this->withToken($current)->getJson(route('auth.me'))->assertUnauthorized();

    $this->app['auth']->forgetGuards();

    $this->withToken($other)->getJson(route('auth.me'))->assertSuccessful();
});

test('a guest is rejected with 401 and the project error envelope', function () {
    $this->getJson(route('auth.me'))
        ->assertUnauthorized()
        ->assertJsonStructure(['message']);
});

test('a bogus bearer token is rejected', function () {
    $this->withToken('1|'.str_repeat('a', 40))
        ->getJson(route('auth.me'))
        ->assertUnauthorized();
});
