ARG FRANKENPHP_VERSION=1.4-php8.4
FROM dunglas/frankenphp:${FRANKENPHP_VERSION}-alpine

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

# Install Node.js & NPM
RUN apk add --no-cache nodejs npm

# COPY install-composer.sh
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Set working directory
WORKDIR /app

# The app code will be volume mounted in DEV mode.
# If building for prod, uncomment:
# COPY . /app
# RUN composer install --no-dev --optimize-autoloader
