<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test case bindings
|--------------------------------------------------------------------------
|
| Feature tests hit the real Postgres test database (see phpunit.xml) and are
| wrapped in a transaction by RefreshDatabase. Unit tests get the framework
| booted but deliberately NOT RefreshDatabase: pure logic must stay fast and
| database-free, otherwise the suite drifts into "everything is a feature test"
| and the gate slows to minutes.
|
*/

uses(TestCase::class, RefreshDatabase::class)->in('Feature');
uses(TestCase::class)->in('Unit');

/*
|--------------------------------------------------------------------------
| Shared helpers
|--------------------------------------------------------------------------
*/

/**
 * Flatten one locale into a `key => value` map: `lang/{locale}/*.php` entries are
 * dot-prefixed with their file (group) name, `lang/{locale}.json` entries are
 * prefixed with `json:` so the two namespaces can never collide.
 *
 * Used by the locale-parity guard and by the "this message really came out of a
 * translation file" assertions.
 *
 * @return array<string, mixed>
 */
function flattenedTranslations(string $locale): array
{
    $flat = [];

    foreach (glob(lang_path($locale.'/*.php')) ?: [] as $file) {
        $group = basename($file, '.php');

        foreach (Arr::dot(require $file) as $key => $value) {
            $flat[$group.'.'.$key] = $value;
        }
    }

    $json = lang_path($locale.'.json');

    if (is_file($json)) {
        foreach ((array) json_decode((string) file_get_contents($json), true) as $key => $value) {
            $flat['json:'.$key] = $value;
        }
    }

    return $flat;
}

/**
 * Pull the issued Sanctum token out of an auth response, wherever the resource
 * envelope happens to put it. Asserting on a fixed JSON path would make every
 * auth test brittle against a resource reshuffle; the token format
 * (`<id>|<40 random chars>`) is the part that is actually contractual.
 *
 * @param  array<mixed>  $payload
 */
function sanctumTokenIn(array $payload): ?string
{
    foreach (Arr::dot($payload) as $value) {
        if (is_string($value) && preg_match('/\|[A-Za-z0-9]{40,}$/', $value) === 1) {
            return $value;
        }
    }

    return null;
}

/**
 * Throwaway tenant-owned table used by the multi-tenancy regression guards.
 *
 * The template ships no domain model, but the scoping machinery
 * (UserOwnedScope + BelongsToAuthUser + ScopedExists) is exactly the part that
 * must never regress — so the guards bring their own table instead of waiting
 * for the first real entity. Postgres runs DDL inside the RefreshDatabase
 * transaction, so this disappears with the rest of the test.
 */
function createOwnedItemsTable(): void
{
    Schema::create('owned_items', function (Blueprint $table) {
        $table->id()->comment('Primary key of the throwaway tenancy fixture');
        $table->foreignId('user_id')->comment('Owner of the row — what UserOwnedScope filters on');
        $table->string('title')->comment('Payload of the fixture row, asserted on in tests');
    });
}
