<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Throwable;

/**
 * Deep health probe behind GET /healthz — point the uptime monitor at THIS, not /up.
 *
 * /up (wired in bootstrap/app.php) only proves PHP booted and is the right probe for a
 * container orchestrator deciding whether to route traffic to a fresh instance. It stays
 * green while Redis is unreachable and every request 500s, which is exactly the outage
 * this project kept having: the monitor said "up" for the entire incident.
 *
 * This endpoint touches the two dependencies whose absence means total failure — the
 * database and Redis (cache, sessions and queues in one) — and answers 503 when either
 * is down, so the alert fires on its own. Both probes run even if the first fails, so
 * the payload names the broken one.
 *
 * Machine endpoint: no auth, no translation, fixed English payload. Exception messages
 * are reduced to a class name on purpose — a connection error string can carry the host,
 * user and sometimes the password, and this response is public.
 *
 * Cheap by design (SELECT 1 + PING), but it is unauthenticated: if it is ever polled
 * hard from outside, put a throttle on the route rather than removing the checks.
 */
class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $probes = [
            'database' => static fn () => DB::connection()->select('select 1'),
            'redis' => static fn () => Redis::connection()->ping(),
        ];

        $checks = [];
        $healthy = true;

        foreach ($probes as $name => $probe) {
            try {
                $probe();
                $checks[$name] = 'ok';
            } catch (Throwable $e) {
                $checks[$name] = 'fail: '.class_basename($e);
                $healthy = false;
            }
        }

        return response()->json([
            'status' => $healthy ? 'ok' : 'degraded',
            'checks' => $checks,
        ], $healthy ? 200 : 503);
    }
}
