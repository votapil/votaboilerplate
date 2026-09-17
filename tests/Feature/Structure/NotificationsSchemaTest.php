<?php

use Illuminate\Support\Facades\Schema;

/**
 * The admin panel enables ->databaseNotifications(), and Filament's notification
 * list filters on `data->format`. Laravel compiles that to `data->>'format'`,
 * which Postgres only offers for json/jsonb — on a text column it raises
 * "operator does not exist: text ->> unknown" and takes down EVERY page of the
 * panel, not just the bell. See PITFALLS row 18.
 *
 * Worth a guard rather than trusting the migration, because Laravel's own
 * make:notifications-table stub writes text('data'): the next person to
 * regenerate that migration reintroduces the outage, and the only symptom is a
 * 500 that looks like a Filament bug.
 */
test('notifications.data is a JSON column', function () {
    expect(Schema::getColumnType('notifications', 'data'))->toBe('json');
});
