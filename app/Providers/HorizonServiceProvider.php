<?php

namespace App\Providers;

use App\Enums\PermissionName;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\HorizonApplicationServiceProvider;

/**
 * Authorization for the operational dashboards.
 *
 * Horizon and Pulse ship as web-session pages bolted onto an application whose real
 * clients authenticate with API tokens, so both need an answer to "who is allowed in"
 * that does not come from the SPA. They share one story, so both gates live here.
 */
class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        $this->pulseGate();
    }

    /**
     * Who can open Horizon outside local.
     *
     * Gate on a permission, never on a hardcoded list of e-mail addresses. The stock
     * stub ships `in_array(optional($user)->email, [])` — an empty allowlist that
     * returns false for everybody, which reads as "queues are broken" instead of
     * "you have no access". In the donor project that stub locked the entire team out
     * of production Horizon and stayed unnoticed for months.
     *
     * The dashboard shows every job's payload and can retry or delete jobs, so it is
     * gated on system administration, not on mere panel access.
     */
    protected function gate(): void
    {
        Gate::define(
            'viewHorizon',
            fn (?User $user = null): bool => (bool) $user?->can(PermissionName::AdminSystem->value),
        );
    }

    /**
     * Who can open Pulse (inert until laravel/pulse is installed).
     *
     * Same permission as Horizon, plus one escape hatch: Pulse runs on web session
     * middleware while the SPA holds only bearer tokens, so a token-only operator has
     * no way to arrive with a session at all. `?secret=` covers that case and is
     * remembered in the session, because Pulse's own Livewire polling requests carry
     * no query string and would otherwise fail the gate on the second render.
     *
     * The secret is deliberately weaker than a session and is read from config, not
     * env(): env() returns null once config is cached, which silently turns the escape
     * hatch off in exactly the environment that needs it. Leave the key unset and the
     * hatch simply does not exist.
     */
    private function pulseGate(): void
    {
        Gate::define('viewPulse', function (?User $user = null): bool {
            if (app()->environment('local')) {
                return true;
            }

            $secret = config('app.pulse_secret');

            if (filled($secret) && request()->query('secret') === $secret) {
                session()->put('pulse_authorized', true);

                return true;
            }

            if (session()->get('pulse_authorized') === true) {
                return true;
            }

            return (bool) $user?->can(PermissionName::AdminSystem->value);
        });
    }
}
