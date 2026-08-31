<?php

namespace App\Filament\Support;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * The one cached aggregate behind every sidebar badge in the panel.
 *
 * Filament builds the whole navigation on EVERY render of EVERY page, and it asks each
 * resource for a badge VALUE, not a closure:
 * `->badge(static::getNavigationBadge(), ...)`
 * (vendor/filament/filament/src/Resources/Resource.php:171). So a resource that runs its
 * own COUNT inside getNavigationBadge() runs it on every click, multiplied by the number
 * of badged resources — under a persistent worker that is a steady drip of aggregate
 * queries against the production database that no profiler attributes to any page.
 *
 * Collect every counter in one pass, cache it for a minute, and let resources read the
 * array. A badge that is a minute stale is not a problem; a badge that costs a query per
 * render is.
 */
final class NavBadges
{
    private const CACHE_KEY = 'admin:nav-badges';

    private const TTL_SECONDS = 60;

    /**
     * Every counter the sidebar can show, in one round trip.
     *
     * @return array<string, int>
     */
    public static function all(): array
    {
        return Cache::remember(self::CACHE_KEY, self::TTL_SECONDS, fn (): array => [
            'users_unverified' => User::query()->whereNull('email_verified_at')->count(),
        ]);
    }

    /**
     * Badge for one navigation item.
     *
     * Returns null rather than "0" on purpose: Filament hides an absent badge and draws a
     * grey zero for a present one, and a sidebar full of zeroes is noise.
     */
    public static function for(string $key): ?string
    {
        $value = self::all()[$key] ?? 0;

        return $value > 0 ? (string) $value : null;
    }

    /** Call after a write whose result must appear in the sidebar immediately. */
    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
