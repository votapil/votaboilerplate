<?php

use App\Http\Controllers\HealthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web routes
|--------------------------------------------------------------------------
|
| The backend is API-first: the only things served here are the health probe
| and the built SPA shell. Filament (/admin), Horizon and Pulse register
| their own routes from their service providers.
|
*/

// Deep health probe. Unlike /up (a shallow "PHP booted" check wired in
// bootstrap/app.php and used by container startup probes), this one actually
// reaches Redis and the database, so an uptime check on it fires when a
// dependency is down instead of reporting green while the app 500s.
// Machine endpoint: no i18n, no auth, fixed English payload.
Route::get('/healthz', HealthController::class)->name('healthz');

// SPA shell. Everything the backend does not own falls through to the
// single-page app built into public/index.html. The parameter is optional so
// that the bare root is covered too — `/{any}` alone does not match `/`.
Route::get('/{any?}', function (Request $request) {
    // On a dedicated admin host the SPA is not what the visitor came for:
    // send the bare root (and any other stray path) to Filament instead.
    if (config('app.admin_domain') && $request->getHost() === config('app.admin_domain')) {
        return redirect('/admin');
    }

    $indexPath = public_path('index.html');

    // Backend-only mode: the SPA has not been built yet.
    if (! file_exists($indexPath)) {
        return response()->view('welcome');
    }

    return response()->file($indexPath);
})
    // The exclusion list is the load-bearing part. Without it this catch-all
    // shadows the admin panel: Filament serves /admin plus its own
    // /filament and /livewire asset routes, and Horizon/Pulse serve their
    // dashboards — all of which register AFTER this file in some orderings,
    // and route:cache freezes whatever order won. Add any new backend web
    // prefix here at the same time you register it. The trailing (?:/|$) makes
    // each entry a whole first segment, so an app route named /uploads or
    // /administrators is not swallowed by the "up" and "admin" entries.
    ->where('any', '^(?!(?:api|sanctum|up|healthz|storage|admin|filament|livewire|horizon|pulse|mcp)(?:/|$)).*$')
    ->name('spa');
