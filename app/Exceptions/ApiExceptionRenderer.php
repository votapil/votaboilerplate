<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Queue\MaxAttemptsExceededException;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * The single place that decides what an API error looks like on the wire and which
 * errors are worth waking somebody up for. Wired from bootstrap/app.php:
 *
 *     ->withExceptions(fn (Exceptions $exceptions) => ApiExceptionRenderer::register($exceptions))
 *
 * Response contract, identical for every failure:
 *
 *     { "message": "<localized>", "code": "<machine readable>", "errors"?: { field: [...] } }
 *
 * `message` is always a translated key — a client never sees an English string baked
 * into PHP, and `abort(403, 'Unauthorized.')` cannot leak one because the status, not
 * the text, is what this class reads. `code` is the stable half: the frontend branches
 * on it, translators never touch it.
 */
final class ApiExceptionRenderer
{
    public static function register(Exceptions $exceptions): void
    {
        $exceptions->render(static fn (Throwable $e, Request $request): ?JsonResponse => self::render($e, $request));

        // Reporting hook. NOTE: returning false here (or calling ->stop() on the returned
        // handler) would also silence Laravel's own logging for that exception — the
        // callback therefore always returns void. Everything the app reports flows through
        // this ONE path, including queue failures (the worker reports job exceptions here
        // too). Do not add a second Queue::failing reporter: two paths is how the alert bot
        // ended up receiving every worker crash twice until people stopped reading it.
        $exceptions->report(static function (Throwable $e): void {
            self::report($e);
        });
    }

    /**
     * Build the JSON body for an API failure, or null to let Laravel render as usual.
     */
    private static function render(Throwable $e, Request $request): ?JsonResponse
    {
        // Filament, Horizon, Pulse and the SPA shell keep Laravel's HTML error pages.
        if (! $request->is('api/*') && ! $request->expectsJson()) {
            return null;
        }

        // Laravel already answers validation in exactly this shape ({message, errors}) and
        // its per-field messages beat any generic sentence we could substitute.
        if ($e instanceof ValidationException) {
            return null;
        }

        // Carries a ready response (Precognition, explicit throws). Rendering it as a 500
        // would discard a perfectly good answer.
        if ($e instanceof HttpResponseException) {
            return null;
        }

        if ($e instanceof BusinessException) {
            return self::json(
                $e->statusCode,
                __($e->messageKey, $e->messageParams),
                self::codeFor($e->messageKey),
                $e,
            );
        }

        if ($e instanceof AuthenticationException) {
            return self::json(401, __('errors.unauthenticated'), 'unauthenticated', $e);
        }

        // By this point Laravel has already converted ModelNotFoundException,
        // AuthorizationException and TokenMismatchException into HTTP exceptions
        // (Handler::prepareException runs before render callbacks), so one branch
        // covers abort(), policies, route-model binding and rate limiting alike.
        if ($e instanceof HttpExceptionInterface) {
            $status = $e->getStatusCode();

            $key = match (true) {
                $status === 401 => 'unauthenticated',
                $status === 403 => 'forbidden',
                $status === 404 => 'not_found',
                $status === 429 => 'too_many_requests',
                $status >= 500 => 'server_error',
                default => null,
            };

            if ($key === null) {
                return null;
            }

            // The key IS the code: one vocabulary for the wire and for lang/*/errors.php,
            // so `code` can never drift from the message the user reads. App\Http\Middleware\
            // EnsurePermission answers 403 with the same 'forbidden' code.
            //
            // Keep the original headers: dropping them costs the client Retry-After
            // on a 429 and WWW-Authenticate on a 401.
            return self::json($status, __("errors.{$key}"), $key, $e, $e->getHeaders());
        }

        return self::json(500, __('errors.server_error'), 'server_error', $e);
    }

    /**
     * @param  array<string, string>  $headers
     */
    private static function json(int $status, string $message, string $code, Throwable $e, array $headers = []): JsonResponse
    {
        $payload = ['message' => $message, 'code' => $code];

        // Debug builds get the cause appended. The contract keys stay exactly the same
        // everywhere, so a test written against local output still holds in production.
        if (config('app.debug')) {
            $payload['debug'] = [
                'exception' => $e::class,
                'message' => $e->getMessage(),
                'at' => str_replace(base_path().'/', '', $e->getFile()).':'.$e->getLine(),
            ];
        }

        return response()->json($payload, $status, $headers);
    }

    /** 'errors.quota_exceeded' -> 'quota_exceeded': the machine-readable half of the response. */
    private static function codeFor(string $messageKey): string
    {
        return Str::afterLast($messageKey, '.');
    }

    /**
     * Forward to the alert channel. Never throws: an error in error reporting would
     * replace a useful 500 with a useless one.
     */
    private static function report(Throwable $e): void
    {
        if (self::isNoise($e)) {
            return;
        }

        try {
            // The channel is a no-op unless TELEGRAM_ERROR_BOT_TOKEN / _CHAT_ID are set,
            // so local and CI runs stay quiet without an environment check here — and
            // staging, which does set them, alerts like production does.
            Log::channel('telegram')->error($e->getMessage(), [
                'exception' => $e::class,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'url' => request()?->fullUrl(),
                'method' => request()?->method(),
                'user_id' => auth()->id(),
            ]);
        } catch (Throwable) {
            // Swallowed deliberately.
        }
    }

    /**
     * Which exceptions are somebody's normal Tuesday rather than an incident.
     *
     * This filter is the difference between an alert channel people read and one they
     * mute. Scanners produce 404s and 401s around the clock; validation failures are the
     * API working as designed; a BusinessException is a rule the app deliberately
     * enforced. All of them still reach the regular log — they just do not page.
     *
     * Public and pure so it can be tested without touching the exception handler.
     */
    public static function isNoise(Throwable $e): bool
    {
        // Any deliberate sub-500 HTTP status is a decision, not a defect: this single
        // line covers abort(403), 404s, 419s and throttling.
        if ($e instanceof HttpExceptionInterface) {
            return $e->getStatusCode() < 500;
        }

        return $e instanceof AuthenticationException
            || $e instanceof ValidationException
            || $e instanceof TokenMismatchException
            || $e instanceof ModelNotFoundException
            || $e instanceof BusinessException
            // Queue mechanics, not a bug: the attempt that actually failed was already
            // reported on its own. Left in, it doubles every queue incident.
            || $e instanceof MaxAttemptsExceededException;
    }
}
