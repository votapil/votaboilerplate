<?php

use App\Exceptions\ApiExceptionRenderer;
use App\Http\Middleware\EnsurePermission;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\SetUserLocale;
use App\Http\Middleware\SetUserScope;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        // Shallow "did PHP boot" probe for container orchestrators. The deep probe that
        // actually pings Redis and the database lives at GET /healthz (routes/web.php);
        // /up stays green even when a dependency is down, so never alert on it alone.
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // The app always runs behind a TLS-terminating proxy (FrankenPHP/Caddy locally,
        // a load balancer in production): it receives plain HTTP with X-Forwarded-Proto.
        // Without this, Laravel and Filament generate http:// URLs and redirects — the
        // classic "/admin login bounces to http and loses the session" symptom.
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO
                | Request::HEADER_X_FORWARDED_AWS_ELB,
        );

        // Every API response must speak the caller's language, authenticated or not
        // (validation errors on register/login are user-facing too), so the locale
        // resolver is global to the group rather than an opt-in alias.
        $middleware->api(append: [
            SetUserLocale::class,
        ]);

        $middleware->alias([
            // Tenant scope: fills App\Support\UserContext so App\Scopes\UserOwnedScope
            // can constrain queries. Opt-in per group — it needs an authenticated user,
            // so it belongs behind auth:sanctum, not on the whole api group.
            'scope.user' => SetUserScope::class,
            'permission' => EnsurePermission::class,
            'role' => EnsureRole::class,
            'locale' => SetUserLocale::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // One place decides what an API error looks like on the wire and how it is
        // reported. Keeping it in a class (instead of inline closures) keeps this file
        // readable and makes the rendering rules testable on their own.
        ApiExceptionRenderer::register($exceptions);
    })->create();
