<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Permission gate for routes. Alias: `permission`, used as
 * `->middleware('permission:admin.users')`.
 *
 * Two deliberate choices:
 *  - the user comes from $request->user(), not from a guard lookup, so the
 *    check behaves identically for a stateless bearer token and a session;
 *  - the 403 body is built here instead of being raised with abort(). A central
 *    exception renderer rewrites HttpException bodies to a generic message, so
 *    an abort(403, __('...')) message is silently discarded — the reason the
 *    caller sees "access denied" and never learns which permission is missing.
 */
final class EnsurePermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->can($permission)) {
            return response()->json([
                'message' => __('auth.forbidden'),
                'code' => 'forbidden',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
