<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->configureModels();
        $this->configurePasswords();
        $this->configureRateLimiting();
    }

    /**
     * Turn the three quiet Eloquent mistakes into loud ones — everywhere but production.
     *
     * "No N+1 queries" as a written rule does not survive contact with a deadline; the
     * project this template comes from carried that rule for six months and still shipped
     * an extra COUNT per row in its busiest endpoint. A rule nothing checks is a wish.
     *
     * shouldBeStrict() enables three guards at once: lazy loading a relation throws,
     * assigning an attribute the model does not have throws, and reading an attribute
     * that was not selected throws. All three are programming errors that otherwise show
     * up as a slow page or a silently discarded field.
     *
     * Off in production on purpose: a missed eager load should cost a user latency, not
     * a 500. Local, CI and staging catch it long before that.
     */
    private function configureModels(): void
    {
        Model::shouldBeStrict(! $this->app->isProduction());
    }

    /**
     * One definition of an acceptable password for registration, reset and change alike.
     * The frontend must compute strength from this same definition or the two screens
     * will disagree about the same string.
     *
     * A warning about symbols(): Laravel reads it as the whole Unicode punctuation,
     * symbol and separator range (\p{Z}\p{S}\p{P}), not a short list like !@#$%^&*. That
     * is wider than users expect — "=" and "-" count — and wider than most homegrown
     * frontend checks, which is where the mismatch bug reports come from.
     */
    private function configurePasswords(): void
    {
        Password::defaults(fn () => Password::min(8)->letters()->numbers()->symbols());
    }

    /**
     * Login throttling. Apply it by naming the limiter in the route middleware —
     * `->middleware('throttle:login')` — rather than an inline `throttle:6,1`, which
     * only ever gives you one bound.
     *
     * Two bounds, because one is not enough: per email+IP stops a brute force against one
     * account, per IP stops the same client spraying one password across many accounts.
     * Keying on the email alone would let anyone lock a victim out of their own account.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('login', function (Request $request): array {
            // Normalized, or " Mail@Example.com " and "mail@example.com" each get their
            // own budget and the per-email bound is decorative.
            $email = mb_strtolower(trim((string) $request->input('email')));

            return [
                Limit::perMinute(5)->by($email.'|'.$request->ip()),
                Limit::perMinute(20)->by((string) $request->ip()),
            ];
        });
    }
}
