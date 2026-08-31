# Development image for the Laravel app (FrankenPHP).
# The code is bind-mounted, nothing is copied in. The production build is a
# separate, self-contained image: docker/prod/Dockerfile.app.
ARG FRANKENPHP_VERSION=1.4-php8.4
FROM dunglas/frankenphp:${FRANKENPHP_VERSION}-alpine

# Your host UID/GID, passed by compose. Everything this container writes into
# the bind mount (vendor/, storage/logs, bootstrap/cache) must belong to you.
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

# groupmod BEFORE usermod, and -g on usermod: without it www-data keeps its old
# primary group and every file it creates still lands with the wrong owner.
RUN apk add --no-cache nodejs npm shadow \
    && groupmod -g ${GID} www-data \
    && usermod -u ${UID} -g ${GID} www-data

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /app

# Caddy stores its state in named volumes. Docker seeds a fresh named volume
# from the image, so these directories must already belong to www-data here —
# otherwise the container drops to www-data and cannot write to its own volume.
RUN mkdir -p /data/caddy /config/caddy \
    && chown -R www-data:www-data /data /config /app

# Never run the dev container as root: root-owned files inside the bind mount
# are the single most common local-setup failure (EACCES on the next build, an
# IDE that cannot save, `make fix-perms` as a daily ritual).
USER www-data
