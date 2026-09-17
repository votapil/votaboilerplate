# =============================================================================
# Production image: FrankenPHP (Octane) + the compiled SPA.
#
# Three stages so the runtime image carries neither composer nor node:
#   1. php-deps     — vendor/ without dev dependencies
#   2. webapp-build — the Nuxt bundle
#   3. final        — FrankenPHP serving /app/public
#
# The SPA is baked into public/, so one container answers both the API and the
# frontend and there is no second origin, no CORS in production, no CDN to keep
# in sync with a deploy.
# =============================================================================

# ----- Stage 1: PHP dependencies ---------------------------------------------
FROM dunglas/frankenphp:1-php8.4-alpine AS php-deps

RUN install-php-extensions \
    pdo_pgsql \
    pgsql \
    gd \
    intl \
    zip \
    opcache \
    pcntl \
    redis \
    bcmath

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /app

# Dependencies first, sources second: editing app code must not re-resolve the
# whole dependency tree on every build.
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

COPY . .
RUN composer dump-autoload --no-dev --optimize --classmap-authoritative


# ----- Stage 2: SPA build -----------------------------------------------------
FROM node:24-alpine AS webapp-build

WORKDIR /app/webapp

COPY webapp/package.json webapp/package-lock.json ./
RUN npm ci --ignore-scripts

COPY webapp/ .

# Empty on purpose: the SPA is served from the same origin as the API, so the
# client calls /api/... relative. Override at build time for a split deploy.
ARG NUXT_PUBLIC_API_BASE=""
ENV NUXT_PUBLIC_API_BASE=${NUXT_PUBLIC_API_BASE}
ENV NODE_ENV=production
RUN npm run generate


# ----- Stage 3: runtime -------------------------------------------------------
FROM dunglas/frankenphp:1-php8.4-alpine

ARG UID=1000
ARG GID=1000

RUN install-php-extensions \
    pdo_pgsql \
    pgsql \
    gd \
    intl \
    zip \
    opcache \
    pcntl \
    redis \
    bcmath

RUN apk add --no-cache shadow \
    && groupmod -g ${GID} www-data \
    && usermod -u ${UID} -g ${GID} www-data

WORKDIR /app

COPY --from=php-deps /app /app

# nuxt generate writes a static bundle to .output/public; dropping it into
# Laravel's public/ makes FrankenPHP serve index.html at / and the PHP
# front controller for everything the SPA does not own.
COPY --from=webapp-build /app/webapp/.output/public /app/public/

# Everything the runtime writes to. /app/public is in the list because Octane
# generates its FrankenPHP worker script there on boot, and /data + /config
# because Caddy keeps its certificates and state there.
RUN mkdir -p \
    /app/storage/logs \
    /app/storage/framework/cache/data \
    /app/storage/framework/sessions \
    /app/storage/framework/views \
    /app/bootstrap/cache \
    /app/resources/views \
    /data/caddy \
    /config/caddy \
    && chown -R www-data:www-data /app/storage /app/bootstrap/cache /app/public /data /config

COPY docker/prod/php.ini /usr/local/etc/php/conf.d/99-prod.ini

EXPOSE 80 443

# Never root in production. Note this is also why the directories above are
# chown-ed at build time: the running container cannot fix them itself.
USER www-data

COPY docker/prod/entrypoint.sh /usr/local/bin/entrypoint.sh
ENTRYPOINT ["sh", "/usr/local/bin/entrypoint.sh"]
