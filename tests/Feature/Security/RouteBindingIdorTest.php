<?php

use App\Models\User;
use App\Support\UserContext;
use Illuminate\Support\Facades\Route;
use Tests\Support\Models\OwnedItem;

/**
 * tests/Feature/Security is a register of regression guards: one file per class of
 * hole that was actually found, named so the link between finding and guard is
 * still readable a year later. Reopening a hole must break the build.
 *
 * This one: cross-tenant IDOR through route-model binding.
 *
 * Route-model binding does NOT go through the global owner scope. SubstituteBindings
 * resolves the model from the raw id before the tenant context exists, so a plain
 * `Route::get('/items/{item}')` happily hands user B the record of user A — the
 * controller never sees an id to check. The fix lives in the BelongsToAuthUser
 * trait, which re-applies the owner filter inside resolveRouteBinding(); these
 * tests are what stops someone "simplifying" it away.
 */
beforeEach(function () {
    createOwnedItemsTable();

    Route::middleware(['api', 'auth:sanctum', 'scope.user'])
        ->get('/api/_test/items/{ownedItem}', fn (OwnedItem $ownedItem) => response()->json(['id' => $ownedItem->id]));

    // Deliberately unauthenticated: binding must not resolve a tenant-owned record
    // when there is nobody to own it.
    Route::middleware('api')
        ->get('/api/_test/open-items/{ownedItem}', fn (OwnedItem $ownedItem) => response()->json(['id' => $ownedItem->id]));
});

test('an owner reaches their own record', function () {
    $owner = User::factory()->create();
    $item = OwnedItem::create(['user_id' => $owner->id, 'title' => 'mine']);

    $this->withToken($owner->createToken('api')->plainTextToken)
        ->getJson("/api/_test/items/{$item->id}")
        ->assertSuccessful()
        ->assertJson(['id' => $item->id]);
});

test('another tenant gets 404, not the record and not a 403', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $item = OwnedItem::create(['user_id' => $owner->id, 'title' => 'not yours']);

    $this->withToken($intruder->createToken('api')->plainTextToken)
        ->getJson("/api/_test/items/{$item->id}")
        ->assertNotFound();
});

test('binding resolves nothing when nobody is authenticated', function () {
    $owner = User::factory()->create();
    $item = OwnedItem::create(['user_id' => $owner->id, 'title' => 'mine']);

    // The dangerous shape of this trait is "if there is a user, filter by them" —
    // which silently degrades to no filter at all on any route that forgets auth.
    $this->getJson("/api/_test/open-items/{$item->id}")->assertNotFound();
});

test('the global scope hides other tenants from ordinary queries', function () {
    $me = User::factory()->create();
    $them = User::factory()->create();

    $mine = OwnedItem::create(['user_id' => $me->id, 'title' => 'mine']);
    OwnedItem::create(['user_id' => $them->id, 'title' => 'theirs']);

    app(UserContext::class)->set($me->id);

    expect(OwnedItem::pluck('id')->all())->toBe([$mine->id]);

    app(UserContext::class)->forget();
});
