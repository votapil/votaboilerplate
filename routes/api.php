<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ClientErrorController;
use App\Http\Controllers\Api\V1\UserSettingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API routes
|--------------------------------------------------------------------------
|
| Mounted by bootstrap/app.php under the "api" prefix, so every path below
| is reachable as /api/v1/... . Keep API controllers in
| App\Http\Controllers\Api\V1 and version the URL from day one: retrofitting
| a version prefix once mobile clients are in the wild is not possible.
|
*/

/*
| Public endpoints. Everything here is reachable without a token, so every
| route carries an explicit rate limit — these are the endpoints credential
| stuffing and mail-bombing aim at.
*/
Route::prefix('v1')->group(function (): void {
    Route::post('auth/register', [AuthController::class, 'register'])
        ->middleware('throttle:10,1')
        ->name('auth.register');

    Route::post('auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:6,1')
        ->name('auth.login');

    Route::post('auth/password-forgot', [AuthController::class, 'passwordForgot'])
        ->middleware('throttle:6,1')
        ->name('auth.password-forgot');

    Route::post('auth/password-reset', [AuthController::class, 'passwordReset'])
        ->middleware('throttle:6,1')
        ->name('auth.password-reset');

    // Frontend crash reporter: the SPA posts its own uncaught errors here so they
    // land in the same channel as backend exceptions instead of dying in a browser
    // console nobody reads. Public on purpose — pre-login pages crash too.
    Route::post('client-errors', [ClientErrorController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('client-errors.store');
});

/*
| Authenticated endpoints.
|
| 'scope.user' fills App\Support\UserContext, which App\Scopes\UserOwnedScope
| reads to constrain every owned model's queries. It must sit on this group,
| not on the whole api group, because it needs a resolved user.
*/
Route::prefix('v1')->middleware(['auth:sanctum', 'scope.user'])->group(function (): void {
    Route::get('auth/me', [AuthController::class, 'me'])
        ->name('auth.me');

    Route::post('auth/logout', [AuthController::class, 'logout'])
        ->name('auth.logout');

    Route::delete('auth/me', [AuthController::class, 'deleteAccount'])
        ->name('auth.delete-account');

    // Per-user preferences (language, timezone, theme) live in their own table,
    // never as extra columns on users — one row per user, so a singleton resource.
    Route::get('settings', [UserSettingController::class, 'show'])
        ->name('settings.show');

    Route::put('settings', [UserSettingController::class, 'update'])
        ->name('settings.update');
});

/*
|--------------------------------------------------------------------------
| Generated routes
|--------------------------------------------------------------------------
|
| `php artisan vota:crud {Model}` APPENDS its apiResource line to the end of
| this file (File::append — it cannot inject into a group). Anything landing
| below therefore has NO auth and NO tenant scope: move each generated line
| into the authenticated group above before you ship it.
|
*/
