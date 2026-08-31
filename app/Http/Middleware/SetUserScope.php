<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\UserContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Fills App\Support\UserContext so App\Scopes\UserOwnedScope can constrain
 * every owned model to the caller. Alias: `scope.user`.
 *
 * Put it on authenticated route groups only. It needs a resolved user, and the
 * contexts that deliberately see everything — console, queue, admin panel —
 * must never pick it up.
 */
final class SetUserScope
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($user = $request->user()) {
            app(UserContext::class)->set($user->getAuthIdentifier());
        }

        return $next($request);
    }

    /**
     * Octane keeps the worker process alive between requests, so anything left
     * behind here becomes the next request's tenant. Clear it explicitly.
     */
    public function terminate(Request $request, Response $response): void
    {
        app(UserContext::class)->forget();
    }
}
