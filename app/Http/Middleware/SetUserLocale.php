<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\UserSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Picks the language for the current request. Alias: `locale`; also applied to
 * the whole api group, so validation errors on the public register and login
 * endpoints — which a person reads before they have an account — come out
 * translated too.
 *
 * A stored preference beats the browser header: someone who chose a language
 * should keep it on a borrowed laptop.
 */
final class SetUserLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->user()?->preferredLocale() ?? $this->fromHeader($request);

        if ($locale !== null) {
            app()->setLocale($locale);
        }

        return $next($request);
    }

    /**
     * First supported language the client asked for, or null.
     *
     * Written by hand rather than with Request::getPreferredLanguage(), which
     * returns the first SUPPORTED locale when the client asked for none of them
     * — silently overriding APP_LOCALE for every visitor with an unrelated
     * language. Region tags are matched on their base ("en-GB" satisfies "en"),
     * which is what people actually mean.
     */
    private function fromHeader(Request $request): ?string
    {
        foreach ($request->getLanguages() as $language) {
            $base = strtolower(substr(str_replace('_', '-', $language), 0, 2));

            if (in_array($base, UserSetting::LOCALES, true)) {
                return $base;
            }
        }

        return null;
    }
}
