<?php

// config for Votapil/VotaCrudGenerator
return [

    /*
    |--------------------------------------------------------------------------
    | Default Namespaces
    |--------------------------------------------------------------------------
    |
    | The package's own default puts controllers in App\Http\Controllers. This
    | project versions its API, so generated controllers go straight into the
    | V1 namespace — otherwise every `vota:crud` run produces a controller that
    | has to be moved by hand, and half of them never are.
    |
    */
    'namespaces' => [
        'model' => 'App\\Models',
        'controller' => 'App\\Http\\Controllers\\Api\\V1',
        'request' => 'App\\Http\\Requests',
        'resource' => 'App\\Http\\Resources',
        'policy' => 'App\\Policies',
        'factory' => 'Database\\Factories',
    ],

    /*
    |--------------------------------------------------------------------------
    | What to Generate
    |--------------------------------------------------------------------------
    */
    'generate' => [
        'model' => true,
        'controller' => true,
        'store_request' => true,
        'update_request' => true,
        'resource' => true,
        'policy' => true,
        'factory' => true,
        'routes' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Smart Detection
    |--------------------------------------------------------------------------
    |
    | The generator introspects the live table: soft deletes, timestamps, casts
    | and foreign keys. Run the migration before the generator, always.
    |
    */
    'detect' => [
        'soft_deletes' => true,
        'timestamps' => true,
        'relationships' => true,
        'casts' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Route Configuration
    |--------------------------------------------------------------------------
    |
    | Generated routes are APPENDED to the end of the route file — the package
    | cannot inject them into an existing group. The prefix below keeps the
    | generated URL versioned, but the appended line still carries no auth and
    | no tenant scope: move it into the authenticated group in routes/api.php.
    |
    */
    'route_file' => 'routes/api.php',
    'route_prefix' => 'v1',

    /*
    |--------------------------------------------------------------------------
    | Optional Packages Integration
    |--------------------------------------------------------------------------
    */
    'packages' => [
        'spatie_query_builder' => false, // spatie/laravel-query-builder
    ],

    /*
    |--------------------------------------------------------------------------
    | Controller Style
    |--------------------------------------------------------------------------
    |
    | 'api' — generates API-only controllers (JSON responses)
    |
    */
    'controller_style' => 'api',

];
