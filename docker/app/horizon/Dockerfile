ARG PHP_VERSION=8.4
FROM php:${PHP_VERSION}-cli-alpine

RUN apk add --no-cache \
    postgresql-dev \
    libpng-dev \
    libzip-dev \
    icu-dev \
    supervisor \
    $PHPIZE_DEPS

RUN docker-php-ext-install pdo_pgsql pgsql gd intl zip opcache pcntl bcmath
RUN pecl install redis && docker-php-ext-enable redis

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

WORKDIR /app
