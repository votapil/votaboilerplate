<?php

use App\Support\UserContext;

/**
 * The tenant context decides which rows every scoped query returns, so a leak here
 * is a data leak. It is a container singleton rather than a class of static
 * properties for exactly that reason: under Octane a static survives the request
 * that set it, and the next request — a different user — inherits it.
 *
 * These are unit tests: no database, no HTTP. The behaviour under test is the
 * lifecycle of the value itself.
 */
test('the context starts empty', function () {
    expect(app(UserContext::class)->userId())->toBeNull();
});

test('a user id can be set and cleared', function () {
    $context = app(UserContext::class);

    $context->set(42);
    expect($context->userId())->toBe(42);

    $context->forget();
    expect($context->userId())->toBeNull();
});

test('the same instance is shared across resolutions', function () {
    // If this ever returns two objects, half the application scopes queries by a
    // context the other half never set.
    app(UserContext::class)->set(7);

    expect(app(UserContext::class)->userId())->toBe(7);
});

test('for() runs a block as another user and restores the previous context', function () {
    $context = app(UserContext::class);
    $context->set(1);

    $seen = null;
    $context->for(2, function () use ($context, &$seen) {
        $seen = $context->userId();
    });

    expect($seen)->toBe(2)
        ->and($context->userId())->toBe(1);
});

test('for() restores the context even when the block throws', function () {
    // This is the whole reason the method exists. A job that fails halfway must not
    // hand its tenant identity to whatever the worker picks up next.
    $context = app(UserContext::class);
    $context->set(1);

    try {
        $context->for(2, function () {
            throw new RuntimeException('boom');
        });
    } catch (RuntimeException) {
        // expected
    }

    expect($context->userId())->toBe(1);
});

test('for() restores an empty context too', function () {
    $context = app(UserContext::class);

    $context->for(5, fn () => null);

    expect($context->userId())->toBeNull();
});
