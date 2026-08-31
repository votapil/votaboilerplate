---
name: laravel-testing-expert
description: Use when writing, fixing or debugging a Pest test — a feature test for a new endpoint, a regression test that reproduces a bug, or a suite that went red. Covers the AAA shape, the endpoint checklist (200/401/403/404/422), filters and parallel-run gotchas. Триггеры — «напиши тест», «покрой тестами», «тесты падают», «make test красный», «регресс-тест», «почему тест не проходит». Это «как написать тест»; обязательный RED-GREEN-гейт внутри Spec Kit — speckit-superb-tdd.
---

# Writing and fixing Pest tests

Feature tests first. A feature test walks the whole lifecycle — route → middleware → controller →
Action → database → JSON — which is where the bugs actually are. Unit tests are for pure calculation
(money, dates, parsing).

## Commands

```bash
make test                               # whole suite, parallel
make test args="--filter=ThingTest"     # one file / one name
make test args="--stop-on-failure"
make artisan args="make:test ThingEndpointTest --pest"
make verify                             # the pre-push gate: pint + pest + vitest + nuxt build
```

The suite runs against **PostgreSQL**, on a separate `*_testing` database, never sqlite — a test that
passes on sqlite and fails on the real driver is worse than no test.

## Shape

```php
<?php

use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;

it('returns only the caller\'s things', function () {
    // Arrange
    $me = User::factory()->create();
    $someoneElse = User::factory()->create();
    Thing::factory()->count(2)->for($me)->create();
    Thing::factory()->for($someoneElse)->create();

    // Act
    $response = actingAs($me)->getJson('/api/v1/things');

    // Assert
    $response->assertOk()->assertJsonCount(2, 'data');
});
```

- `it('...')` / `test('...')`, description reads as a sentence. No PHPUnit classes.
- Arrange / Act / Assert, separated by blank lines.
- `RefreshDatabase` is wired globally in `tests/Pest.php` — do not re-add it per file.
- Factories build the world. No hand-rolled inserts, no fixtures copied between tests.

## Checklist per endpoint

| Case | Expect |
|---|---|
| Happy path | 200/201 + `assertJsonStructure` on the Resource shape |
| No token | 401 |
| Token without the permission | 403 |
| Someone else's id in the URL | **404**, not 403 — scoped route binding must not confirm existence |
| Invalid / missing fields | 422 + the field names in `errors` |
| List endpoint | pagination envelope, and no N+1 (`assertDatabaseCount` / query count) |

The "someone else's id" row is the one that catches the real security bug: route-model binding is
resolved *before* the scope middleware, so it is the model trait's `resolveRouteBinding()` that has to
404. Test it on every new endpoint.

## Regression tests for bug fixes

When the bug lives in logic that could plausibly recur — calculations, money, scoping, state
transitions, API contracts — write the failing test **first**, watch it fail for the right reason, then
fix. The failing-then-passing test is the proof and the guard.

Skip the ceremony for one-off fixes with nothing to protect: a typo in a translation, a static value
corrected, a pure styling tweak.

## When the suite is red

1. Read the **first** failure, not the last. Later failures are usually fallout.
2. Re-run just it: `make test args="--filter=TheFailingTest"`. If it passes alone, the problem is
   shared state or a parallel collision, not the assertion.
3. Two `make test` runs at once share one test database and produce fake red. Check nothing else is
   running before believing a failure.
4. Red without a code change is an environment problem — see skill `env-doctor`.
