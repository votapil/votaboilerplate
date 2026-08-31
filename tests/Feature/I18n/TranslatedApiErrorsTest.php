<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;

/**
 * Two halves of the same rule: nothing a user can read is hardcoded English, and
 * the locale is actually negotiated per request.
 *
 * The probe route is defined here rather than borrowed from the app so the guard
 * keeps working in a project that has replaced every domain route — what is under
 * test is the middleware wiring (`permission` alias, `locale` on the api group),
 * not any particular endpoint.
 */
beforeEach(function () {
    Permission::findOrCreate('i18n.probe', 'web');

    Route::middleware(['api', 'auth:sanctum', 'permission:i18n.probe'])
        ->get('/api/_test/gated', fn () => response()->json(['ok' => true]));

    Route::middleware('api')
        ->get('/api/_test/locale', fn () => response()->json(['locale' => app()->getLocale()]));
});

test('a user without the permission is refused with 403', function () {
    $user = User::factory()->create();

    $this->withToken($user->createToken('probe')->plainTextToken)
        ->getJson('/api/_test/gated')
        ->assertForbidden()
        ->assertJsonStructure(['message']);
});

test('a user holding the permission gets through', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('i18n.probe');

    $this->withToken($user->createToken('probe')->plainTextToken)
        ->getJson('/api/_test/gated')
        ->assertSuccessful();
});

test('the 403 message is translated, not a hardcoded framework string', function () {
    $user = User::factory()->create();

    $message = $this->withToken($user->createToken('probe')->plainTextToken)
        ->getJson('/api/_test/gated')
        ->json('message');

    expect($message)->toBeString()->not->toBe('');

    // Every one of these is what you get when nobody wrote a translation.
    expect($message)->not->toBeIn([
        'Forbidden',
        'Unauthorized.',
        'This action is unauthorized.',
        'User does not have the right permissions.',
    ]);

    // Stronger form of the same rule: the string has to have come out of a
    // translation file, so it exists in every other locale too (see LocaleParityTest).
    expect(in_array($message, array_values(flattenedTranslations('en')), true))
        ->toBeTrue("403 message [{$message}] is not a value in any lang/en file — user-facing text must go through __()");
});

test('the request locale follows the Accept-Language header', function () {
    $this->withHeader('Accept-Language', 'ru')
        ->getJson('/api/_test/locale')
        ->assertJson(['locale' => 'ru']);
});

test('an unknown or missing Accept-Language falls back to the app locale', function () {
    $this->getJson('/api/_test/locale')
        ->assertJson(['locale' => config('app.locale')]);

    $this->withHeader('Accept-Language', 'kl-GL')
        ->getJson('/api/_test/locale')
        ->assertJson(['locale' => config('app.locale')]);
});
