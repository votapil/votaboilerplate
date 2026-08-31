<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Role gate for routes. Alias: `role`, used as
 * `->middleware('role:admin')` or `->middleware('role:admin,support')`.
 *
 * Prefer `permission:` over `role:` for anything an administrator may want to
 * re-delegate later — roles are a grouping, permissions are the capability.
 */
final class EnsureRole
{
    /**
     * @param  string  ...$roles  any one of these is enough
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        // hasAnyRole(), never hasRole(): Spatie's second argument to hasRole()
        // is the GUARD name, so hasRole('admin', 'support') silently asks
        // "does this user have the admin role on the support guard?" and
        // answers no for everybody.
        if ($user === null || ! $user->hasAnyRole($roles)) {
            return response()->json([
                'message' => __('auth.forbidden'),
                'code' => 'forbidden',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
