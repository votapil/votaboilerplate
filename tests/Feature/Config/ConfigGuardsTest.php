<?php

/**
 * Guards over configuration, not over code.
 *
 * Every entry here corresponds to a production incident that no feature test could
 * have caught, because nothing was wrong with the logic — a value in a config file
 * was wrong, and the failure mode was silence. A config guard costs three lines and
 * survives the next person who "just tries" a different setting.
 */
test('the Octane permission reset listener is enabled', function () {
    // Under Octane a worker process outlives the request, and Spatie caches the
    // permission map in that process. Without this listener a role granted in the
    // admin panel is invisible until the container restarts — which reads to
    // everyone involved as "permissions are broken".
    //
    // Related operational note: console workers (Horizon) hold the same cache, so a
    // manual permission edit in production still needs `horizon:terminate`.
    expect(config('permission.register_octane_reset_listener'))->toBeTrue();
});

test('every filesystem disk throws instead of failing silently', function () {
    // Flysystem's default is to swallow write failures and return false. A bucket
    // misconfiguration then drops uploads with no exception, no log line and no
    // failed job — files simply stop existing, and nobody finds out for weeks.
    foreach (config('filesystems.disks') as $name => $disk) {
        expect($disk['throw'] ?? false)->toBeTrue(
            "Filesystem disk [{$name}] has throw disabled — write failures will be invisible"
        );
    }
});

test('APP_DEBUG defaults to off when the variable is missing', function () {
    // A fail-safe default matters more than the value in any particular .env:
    // a container started without the variable must not serve stack traces,
    // environment dumps and query logs to the public internet.
    $previousEnv = $_ENV['APP_DEBUG'] ?? null;
    $previousServer = $_SERVER['APP_DEBUG'] ?? null;
    $previousPutenv = getenv('APP_DEBUG');

    unset($_ENV['APP_DEBUG'], $_SERVER['APP_DEBUG']);
    putenv('APP_DEBUG');

    try {
        $app = require config_path('app.php');

        expect($app['debug'])->toBeFalse("config/app.php must read env('APP_DEBUG', false), never a truthy default");
    } finally {
        if ($previousEnv !== null) {
            $_ENV['APP_DEBUG'] = $previousEnv;
        }

        if ($previousServer !== null) {
            $_SERVER['APP_DEBUG'] = $previousServer;
        }

        if ($previousPutenv !== false) {
            putenv('APP_DEBUG='.$previousPutenv);
        }
    }
});

test('the queue never shares a Redis keyspace with the cache', function () {
    // A cache instance is normally configured with an LRU eviction policy. Queue
    // payloads living in that same keyspace get evicted under memory pressure —
    // jobs vanish with no failure, no retry and no log entry. Keeping them on
    // separate connections and separate databases is what makes an eviction policy
    // safe to set at all.
    $queueConnection = config('queue.connections.redis.connection', 'default');
    $cacheConnection = config('cache.stores.redis.connection', 'default');

    expect($queueConnection)->not->toBe($cacheConnection, 'queue and cache must use different Redis connections');

    expect(config("database.redis.{$queueConnection}.database"))
        ->not->toBe(config("database.redis.{$cacheConnection}.database"), 'queue and cache must use different Redis databases');
});

test('Redis connections have sane timeouts', function () {
    // With the default timeout of 0 (infinite), a Redis instance that hangs takes
    // the whole site with it: every request blocks until max_execution_time and
    // dies with a fatal, flooding the error channel. A bounded connect timeout
    // turns a Redis outage into fast errors instead of a total stall.
    $queueConnection = config('queue.connections.redis.connection', 'default');
    $cacheConnection = config('cache.stores.redis.connection', 'default');

    $queue = config("database.redis.{$queueConnection}");
    $cache = config("database.redis.{$cacheConnection}");

    expect((float) ($queue['timeout'] ?? 0))->toBeGreaterThan(0, "redis.{$queueConnection} needs a connect timeout");
    expect((float) ($cache['timeout'] ?? 0))->toBeGreaterThan(0, "redis.{$cacheConnection} needs a connect timeout");

    // ...but NOT a read timeout on the queue connection: Horizon and the queue
    // worker use blocking pops, and a read timeout aborts them mid-wait.
    expect((float) ($queue['read_timeout'] ?? 0))->toBe(
        0.0,
        "redis.{$queueConnection} must not set read_timeout — blocking pops would be cut off"
    );

    expect((float) ($cache['read_timeout'] ?? 0))->toBeGreaterThan(0, "redis.{$cacheConnection} needs a read timeout");
});
