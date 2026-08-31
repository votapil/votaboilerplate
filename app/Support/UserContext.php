<?php

declare(strict_types=1);

namespace App\Support;

use Closure;

/**
 * Holds the id of the user whose rows the current request or job is allowed to
 * see. Written by App\Http\Middleware\SetUserScope (alias `scope.user`), read by
 * App\Scopes\UserOwnedScope.
 *
 * It is empty outside HTTP on purpose: console commands, queue workers and the
 * admin panel are meant to see everything. Use for() when a job needs to act on
 * behalf of one particular user.
 *
 * Why an object in the container rather than a class with static properties:
 * under Octane the worker process survives the request, so a static value stays
 * put and the next request — a different person — inherits the previous
 * tenant's identity. The container instance dies with the application, which
 * also means every test starts with an empty context and nothing has to be
 * reset by hand.
 *
 * Resolve it with app(UserContext::class), or type-hint it in a constructor.
 */
final class UserContext
{
    private ?int $userId = null;

    /**
     * The container resolves an unbound concrete class fresh on every call,
     * which would hand each caller its own private context. Registering the
     * first instance makes every later resolution return this one.
     *
     * A `scoped()` binding in a service provider does the same job and takes
     * precedence if one is ever added — then this branch simply never runs.
     */
    public function __construct()
    {
        if (! app()->bound(self::class)) {
            app()->instance(self::class, $this);
        }
    }

    public function set(?int $userId): void
    {
        $this->userId = $userId;
    }

    public function userId(): ?int
    {
        return $this->userId;
    }

    public function forget(): void
    {
        $this->userId = null;
    }

    /**
     * Run $callback as if $userId were the authenticated user, then put the
     * previous context back — including when the callback throws.
     *
     * This is the sanctioned way to touch owned rows outside a request. The
     * alternative people reach for, withoutGlobalScopes(), does not narrow the
     * query to somebody else: it removes the filter entirely and returns EVERY
     * tenant's rows. Reach for it only when "all tenants" is genuinely what you
     * mean.
     *
     * @template TReturn
     *
     * @param  Closure(): TReturn  $callback
     * @return TReturn
     */
    public function for(int $userId, Closure $callback): mixed
    {
        $previous = $this->userId;

        $this->userId = $userId;

        try {
            return $callback();
        } finally {
            $this->userId = $previous;
        }
    }
}
