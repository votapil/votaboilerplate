<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\User;
use App\Scopes\UserOwnedScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Marks a model as owned by one user. Add it to any model with a `user_id`
 * column and every query, plus route-model binding, is filtered by owner.
 *
 * @property int $user_id
 */
trait BelongsToAuthUser
{
    public static function bootBelongsToAuthUser(): void
    {
        static::addGlobalScope(new UserOwnedScope);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Resolve a route parameter to a record the current user owns, or nothing.
     *
     * SECURITY — this override is the single most important line in the tenancy
     * setup. Route-model binding runs inside SubstituteBindings, which Laravel
     * sorts BEFORE the `scope.user` middleware, so UserContext is still empty
     * and UserOwnedScope is INACTIVE while the binding happens. Without the
     * explicit owner filter below, `GET /api/v1/things/{thing}` would happily
     * hand any id to any caller: a textbook cross-tenant IDOR that no test of
     * the controller itself would notice.
     *
     * Authenticate DOES run before SubstituteBindings (it sits high in Laravel's
     * middleware priority list), so auth()->user() is available here.
     *
     * When there is no authenticated user we resolve NOTHING — returning null
     * makes the router raise a 404. Do not "helpfully" fall back to an unscoped
     * lookup: a public route that binds an owned model would then leak every
     * row in the table.
     */
    public function resolveRouteBinding($value, $field = null): ?Model
    {
        $user = auth()->user();

        if ($user === null) {
            return null;
        }

        $table = $this->getTable();

        return static::query()
            ->where($table.'.'.($field ?: $this->getRouteKeyName()), $value)
            ->where($table.'.user_id', $user->getAuthIdentifier())
            ->first();
    }
}
