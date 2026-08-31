# Development image for the background containers (worker + scheduler).
# Plain PHP CLI: no web server, no Caddy. Production: docker/prod/Dockerfile.worker.
ARG PHP_VERSION=8.4
FROM php:${PHP_VERSION}-cli-alpine

ARG UID=1000
ARG GID=1000

RUN apk add --no-cache \
    postgresql-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    icu-dev \
    shadow \
    $PHPIZE_DEPS \
    && groupmod -g ${GID} www-data \
    && usermod -u ${UID} -g ${GID} www-data

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_pgsql pgsql gd intl zip opcache pcntl bcmath
RUN pecl install redis && docker-php-ext-enable redis

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

WORKDIR /app

# Same reason as the app image: this container writes logs and failed-job
# payloads into the bind mount, and root-owned files there break the host.
USER www-data
