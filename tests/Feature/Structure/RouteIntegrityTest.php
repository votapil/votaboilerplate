<?php

use App\Http\Middleware\SetUserScope;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Support\Facades\Route;

/**
 * Invariants over the whole route table. These cost nothing to run and cover
 * endpoints that do not exist yet — including the ones a code generator will
 * append tomorrow.
 */

/**
 * Routes that are meant to be reachable without a token. Everything else under
 * api/v1 must be authenticated, so adding a public endpoint is a deliberate,
 * reviewable edit to this list rather than an omission nobody notices.
 */
const PUBLIC_API_ROUTES = [
    'auth.register',
    'auth.login',
    'auth.password-forgot',
    'auth.password-reset',
    'client-errors.store',
];

test('every route points at a controller action that exists', function () {
    foreach (Route::getRoutes() as $route) {
        $action = $route->getAction('uses');

        if (! is_string($action) || ! str_contains($action, '@')) {
            continue; // closure route
        }

        [$class, $method] = explode('@', $action, 2);

        expect(class_exists($class))->toBeTrue("Route [{$route->uri()}] points at missing class {$class}");
        expect(method_exists($class, $method))->toBeTrue("Route [{$route->uri()}] points at missing method {$class}::{$method}()");
    }
});

test('no OAuth routes are registered', function () {
    // The template authenticates with Sanctum. Passport used to be a dependency
    // here; it auto-registers /oauth/* and, with no signing keys provisioned, every
    // bot that probed those URLs turned into a 500 in the error channel.
    $oauth = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route) => str_starts_with($route->uri(), 'oauth'))
        ->map(fn ($route) => $route->uri())
        ->values()
        ->all();

    expect($oauth)->toBeEmpty('Passport-style /oauth routes are back: '.implode(', ', $oauth));
});

test('every api/v1 route is authenticated and tenant-scoped unless explicitly public', function () {
    $router = app('router');
    $checked = 0;

    foreach (Route::getRoutes() as $route) {
        if (! str_starts_with($route->uri(), 'api/v1')) {
            continue;
        }

        if (in_array($route->getName(), PUBLIC_API_ROUTES, true)) {
            continue;
        }

        $middleware = collect($router->gatherRouteMiddleware($route))
            ->filter(fn ($m) => is_string($m))
            ->map(fn (string $m) => explode(':', $m, 2)[0])
            ->all();

        $label = $route->methods()[0].' '.$route->uri().' ('.($route->getName() ?? 'unnamed').')';

        // The CRUD generator appends its routes to the very end of routes/api.php —
        // outside every group, therefore without auth and without the tenant scope.
        // This is the guard that catches that the moment it happens.
        expect(in_array(Authenticate::class, $middleware, true))->toBeTrue(
            "{$label} is not behind auth — move it into the authenticated v1 group, or add it to PUBLIC_API_ROUTES on purpose"
        );

        expect(in_array(SetUserScope::class, $middleware, true))->toBeTrue(
            "{$label} is authenticated but not tenant-scoped — without scope.user the global owner filter never activates"
        );

        $checked++;
    }

    expect($checked)->toBeGreaterThan(0, 'no authenticated api/v1 routes found — this guard would be vacuous');
});

test('every public api route is rate limited', function () {
    // Unauthenticated endpoints are the ones that get hammered: credential
    // stuffing on login, mail-bombing through password reset, log flooding
    // through the client error sink.
    $router = app('router');

    foreach (Route::getRoutes() as $route) {
        if (! in_array($route->getName(), PUBLIC_API_ROUTES, true)) {
            continue;
        }

        $throttled = collect($router->gatherRouteMiddleware($route))
            ->contains(fn ($m) => is_string($m) && str_contains($m, 'ThrottleRequests'));

        expect($throttled)->toBeTrue("Public route [{$route->uri()}] has no throttle middleware");
    }
});
