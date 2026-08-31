<?php

namespace Tests;

use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Cache;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // RefreshDatabase rolls the database back per test, but the cache is not
        // transactional and survives into the next one. Anything version-keyed or
        // memoised (permission cache, config-derived registries) would then serve a
        // snapshot built by a previous test — a class of failure that only shows up
        // when the suite order changes, i.e. never locally and always on CI.
        Cache::flush();

        // Roles and permissions are needed by practically every feature test: gates,
        // policies and the Filament panel all read them. Seeding here instead of in
        // each file's beforeEach() is deliberate — the same seed() call was copied
        // into 67 test files of the project this template was distilled from.
        //
        // Rule of thumb for what belongs here: reference data that more than half of
        // the suite needs. Everything else stays local to the test that needs it.
        if ($this->usesRefreshDatabase()) {
            $this->seed(RolesAndPermissionsSeeder::class);
        }

        // Note there is no tenant-context reset here on purpose: UserContext is a
        // container singleton, so a fresh application per test is a fresh context.
        // A static holder would have forced every test to clean up after itself.
    }

    /** Unit tests run without a database — they must not touch it during setUp(). */
    private function usesRefreshDatabase(): bool
    {
        return in_array(RefreshDatabase::class, class_uses_recursive(static::class), true);
    }
}
