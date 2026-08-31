{{--
    Backend-only placeholder. It is rendered by the SPA catch-all in
    routes/web.php when public/index.html does not exist yet, i.e. before the
    Nuxt app in webapp/ has been built.

    This file is also why resources/views/ must never be empty: `view:cache`
    in a production image fails outright when the directory is missing, and
    the failure surfaces at deploy time, not at build time.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ config('app.name') }}</title>
    <style>
        :root { color-scheme: light dark; }
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            font: 16px/1.6 ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif;
            background: Canvas;
            color: CanvasText;
        }
        main { max-width: 34rem; padding: 2rem; }
        h1 { font-size: 1.5rem; margin: 0 0 .5rem; }
        p { margin: 0 0 1.5rem; opacity: .75; }
        ul { margin: 0; padding-left: 1.1rem; }
        li { margin-bottom: .35rem; }
        code { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: .9em; }
    </style>
</head>
<body>
    <main>
        <h1>{{ config('app.name') }} — API is running</h1>
        <p>The frontend has not been built yet, so this placeholder is served instead.</p>
        <ul>
            <li>API: <code>/api/v1</code></li>
            <li>Admin panel: <code>/admin</code></li>
            <li>Health probe: <code>/healthz</code></li>
            <li>Build the SPA in <code>webapp/</code> to replace this page.</li>
        </ul>
    </main>
</body>
</html>
