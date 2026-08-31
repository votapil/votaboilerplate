<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Password reset, end to end: request a link, follow the token, land on a new
 * password. The interesting part is the tail — a reset must not leave the old
 * password usable and must not leave stale API tokens alive, because "I reset my
 * password" is what a user does after an account takeover.
 */

/** Trigger a reset mail and return the token that was actually emailed. */
function requestResetToken(TestCase $case, User $user): string
{
    $case->postJson(route('auth.password-forgot'), ['email' => $user->email])
        ->assertSuccessful();

    $token = null;

    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use (&$token) {
        $token = $notification->token;

        return true;
    });

    return $token;
}

test('requesting a reset link sends the notification', function () {
    Notification::fake();

    $user = User::factory()->create(['email' => 'ada@example.test']);

    $this->postJson(route('auth.password-forgot'), ['email' => 'ada@example.test'])
        ->assertSuccessful();

    Notification::assertSentTo($user, ResetPassword::class);
});

test('the emailed token sets a new password and cannot be replayed', function () {
    Notification::fake();

    $user = User::factory()->create([
        'email' => 'ada@example.test',
        'password' => Hash::make('the-old-password'),
    ]);

    $payload = [
        'token' => requestResetToken($this, $user),
        'email' => 'ada@example.test',
        'password' => 'a-brand-new-password-1',
        'password_confirmation' => 'a-brand-new-password-1',
    ];

    $this->postJson(route('auth.password-reset'), $payload)->assertSuccessful();

    expect(Hash::check('a-brand-new-password-1', $user->fresh()->password))->toBeTrue()
        ->and(Hash::check('the-old-password', $user->fresh()->password))->toBeFalse();

    // Single use: the same token must not open the account a second time.
    expect($this->postJson(route('auth.password-reset'), $payload)->isSuccessful())->toBeFalse();
});

test('a reset revokes the API tokens issued before it', function () {
    // Regression barrier: a reset is what a user does after a takeover. If the
    // attacker's bearer token survives it, the reset achieves nothing.
    Notification::fake();

    $user = User::factory()->create(['email' => 'ada@example.test']);
    $stolen = $user->createToken('stolen')->plainTextToken;

    $this->postJson(route('auth.password-reset'), [
        'token' => requestResetToken($this, $user),
        'email' => 'ada@example.test',
        'password' => 'a-brand-new-password-1',
        'password_confirmation' => 'a-brand-new-password-1',
    ])->assertSuccessful();

    $this->withToken($stolen)->getJson(route('auth.me'))->assertUnauthorized();
});
