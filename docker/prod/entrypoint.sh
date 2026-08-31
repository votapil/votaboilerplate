#!/bin/sh
# Production entrypoint for the app image: warm the caches, migrate (unless told
# not to), then hand over to Octane.
set -e

echo "==> production entrypoint"

mkdir -p /app/resources/views /app/storage/logs /app/storage/framework/cache/data \
    /app/storage/framework/sessions /app/storage/framework/views /app/bootstrap/cache

php artisan config:cache
php artisan route:cache
php artisan view:cache || echo "    view:cache skipped (no views)"
php artisan event:cache

# One instance must migrate, not all of them. On a single-container host the
# default is fine; on anything autoscaled (several instances start at once, in
# any order) set RUN_MIGRATIONS=false and run the migrations as a separate
# one-off job before the new revision takes traffic.
if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
  php artisan migrate --force --no-interaction
else
  echo "==> RUN_MIGRATIONS=false — skipping migrations (a separate job owns them)"
fi

php artisan storage:link 2>/dev/null || true

echo "==> starting FrankenPHP/Octane on port ${PORT:-80}"

# Port: honours an injected $PORT (most managed runtimes assign one), 80 otherwise.
# TLS: driven by CADDY_SERVER_NAME → config/octane.php caddy.env.
#   * behind your own domain    → CADDY_SERVER_NAME=example.com, Caddy obtains
#                                 and renews the certificate itself;
#   * behind a TLS-terminating
#     load balancer / PaaS      → CADDY_SERVER_NAME=":$PORT", plain HTTP inside.
# Getting this wrong is the classic first-deploy day: the container answers on
# the wrong scheme and every request redirects forever.
#
# --max-requests recycles each worker to bound the damage of a slow leak in a
# long-lived process.
exec php artisan octane:frankenphp --host=0.0.0.0 --port="${PORT:-80}" --max-requests=1000
