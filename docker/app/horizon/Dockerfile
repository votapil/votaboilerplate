ARG PHP_VERSION=8.4
FROM php:${PHP_VERSION}-cli-alpine

ARG UID=1000
ARG GID=1000

RUN apk add --no-cache \
    postgresql-dev \
    libpng-dev \
    libzip-dev \
    icu-dev \
    supervisor \
    shadow \
    $PHPIZE_DEPS

# Fix Permissions
RUN usermod -u ${UID} www-data \
    && groupmod -g ${GID} www-data

RUN docker-php-ext-install pdo_pgsql pgsql gd intl zip opcache pcntl bcmath
RUN pecl install redis && docker-php-ext-enable redis

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

WORKDIR /app
