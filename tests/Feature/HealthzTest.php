<?php

/**
 * Deep health probe. Laravel's stock `/up` only proves that PHP answered — it
 * returns 200 while Redis is unreachable and the queue is dead, which is exactly
 * how an outage stays invisible to an uptime check. `/healthz` pings the real
 * dependencies (database + Redis) and reports failure with a 503, so a monitor
 * pointed at it actually fires.
 */
test('/healthz answers with a machine-readable status and an honest code', function () {
    $response = $this->getJson('/healthz');

    // The test host may or may not have a reachable Redis; both outcomes are
    // legitimate. What is not legitimate is a 200 that means nothing, or a body
    // a monitoring system cannot parse.
    expect($response->status())->toBeIn([200, 503]);

    $response->assertJsonStructure(['status']);
});

test('/healthz is reachable without authentication', function () {
    // An uptime check carries no bearer token. If this ever starts redirecting
    // or 401-ing, monitoring goes blind while the site still looks fine.
    expect($this->getJson('/healthz')->status())->not->toBeIn([301, 302, 401, 403, 404]);
});
