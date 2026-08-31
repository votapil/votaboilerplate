<?php

use App\Models\User;
use App\Rules\ScopedExists;
use App\Support\UserContext;
use Illuminate\Support\Facades\Validator;
use Tests\Support\Models\OwnedItem;

/**
 * Regression guard: mass-assigning a foreign key you do not own.
 *
 * `exists:owned_items,id` compiles to a raw SQL EXISTS. It bypasses the global
 * owner scope entirely, so validation happily accepts another tenant's id — and
 * the record is then created pointing at someone else's data, or a report quietly
 * aggregates across tenants. ScopedExists resolves the id through the model
 * instead, so the same query the user's reads go through decides the answer.
 */
beforeEach(function () {
    createOwnedItemsTable();
});

test('a foreign id fails validation', function () {
    $me = User::factory()->create();
    $them = User::factory()->create();
    $theirs = OwnedItem::create(['user_id' => $them->id, 'title' => 'theirs']);

    app(UserContext::class)->set($me->id);

    $validator = Validator::make(
        ['item_id' => $theirs->id],
        ['item_id' => [new ScopedExists(OwnedItem::class)]],
    );

    expect($validator->fails())->toBeTrue('ScopedExists accepted an id belonging to another tenant');

    app(UserContext::class)->forget();
});

test('an owned id passes validation', function () {
    $me = User::factory()->create();
    $mine = OwnedItem::create(['user_id' => $me->id, 'title' => 'mine']);

    app(UserContext::class)->set($me->id);

    $validator = Validator::make(
        ['item_id' => $mine->id],
        ['item_id' => [new ScopedExists(OwnedItem::class)]],
    );

    expect($validator->fails())->toBeFalse();

    app(UserContext::class)->forget();
});

test('a missing id fails and a null value is left to other rules', function () {
    $me = User::factory()->create();

    app(UserContext::class)->set($me->id);

    expect(Validator::make(['item_id' => 999999], ['item_id' => [new ScopedExists(OwnedItem::class)]])->fails())
        ->toBeTrue();

    // Optionality belongs to `nullable`/`required`, not to this rule — otherwise
    // every optional relation needs a second, contradictory rule to stay optional.
    expect(Validator::make(['item_id' => null], ['item_id' => [new ScopedExists(OwnedItem::class)]])->fails())
        ->toBeFalse();

    app(UserContext::class)->forget();
});
