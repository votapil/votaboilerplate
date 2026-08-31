<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Crash reporter for the frontend: POST /api/v1/client-errors.
 *
 * Without it, a SPA that breaks on a device you do not own breaks silently — the stack
 * trace dies in a browser console nobody will ever read, and the backend logs look
 * perfectly healthy. This puts client failures in the same channel as server ones.
 *
 * The endpoint is public (pre-login screens crash too), which makes it the easiest thing
 * in the API to abuse: anyone can point an alert channel at itself. Three limits, all
 * load-bearing:
 *
 *   - throttle:10,1 on the route (routes/api.php) — per IP;
 *   - per-field length caps below, so one report cannot be a megabyte;
 *   - the channel's own per-minute cap in App\Logging\TelegramLogHandler.
 *
 * Reports are logged locally in every environment and relayed to the alert channel only
 * where that channel is configured.
 */
class ClientErrorController extends Controller
{
    public function store(Request $request): Response
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
            'stack' => ['nullable', 'string', 'max:5000'],
            'url' => ['nullable', 'string', 'max:500'],
            'component' => ['nullable', 'string', 'max:200'],
            'user_agent' => ['nullable', 'string', 'max:300'],
        ]);

        $context = [
            'exception' => $validated['component'] ?? 'ClientError',
            'url' => $validated['url'] ?? null,
            'trace' => $validated['stack'] ?? null,
            'user_agent' => $validated['user_agent'] ?? $request->userAgent(),
            'user_id' => $request->user()?->id,
        ];

        Log::warning('Client error: '.$validated['message'], $context);

        try {
            Log::channel('telegram')->error('Client error: '.$validated['message'], $context);
        } catch (\Throwable) {
            // A report about a broken client must not itself break.
        }

        // Nothing to hand back: the client is already in a failure path and has no use
        // for a body. 204 also keeps this endpoint out of the JsonResource contract.
        return response()->noContent();
    }
}
