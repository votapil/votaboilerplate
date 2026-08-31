<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | The backend (app container) and the SPA (webapp container) run on
    | different ports from the very first request, so this file is load-bearing
    | on day one, not a production afterthought. Every origin the SPA is served
    | from must be listed here or the browser blocks the API call before it
    | leaves the page.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_filter([
        env('FRONTEND_URL', 'http://localhost:3000'),
        'http://localhost:3000',
        'http://127.0.0.1:3000',
    ]),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    /*
     * Auth is bearer-token only (Sanctum personal access tokens), so the browser
     * never needs to attach cookies cross-origin. Keep this false: flipping it to
     * true forbids the '*' wildcards above and silently widens the CSRF surface.
     */
    'supports_credentials' => false,

];
