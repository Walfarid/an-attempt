# syntax=docker/dockerfile:1
# Production image for the walfa Laravel + Inertia app.
#
# Runtime: FrankenPHP (Caddy + PHP 8.5 in one binary) with Laravel Octane
# worker mode. Multi-arch build (linux/amd64 + linux/arm64) is done in CI via
# buildx; the VM is arm64.
#
# Base images verified 2026-08-27 against Docker Hub:
#   dunglas/frankenphp:1.12.7-php8.5-bookworm
# Node tarball: v24.19.0 (matches local dev, Node 24 line used by CI).

# ---- PHP dependencies ------------------------------------------------------
# Runs on the build platform (amd64 in CI): PHP packages are
# platform-independent, so one build serves both target arches.
FROM --platform=$BUILDPLATFORM dunglas/frankenphp:1.12.7-php8.5-bookworm AS vendor
WORKDIR /app
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip \
    && rm -rf /var/lib/apt/lists/*
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --no-scripts --prefer-dist
COPY . .
# Framework-writable dirs are gitignored, so recreate them before booting
# artisan (octane:install needs a valid cache path).
RUN mkdir -p storage/framework/cache/data storage/framework/sessions \
    storage/framework/views storage/logs
# octane:install drops the gitignored public/frankenphp-worker.php that the
# Caddyfile worker mode needs.
RUN php artisan octane:install --server=frankenphp --no-interaction \
    && php artisan package:discover --ansi \
    && composer dump-autoload --optimize --no-interaction --no-scripts

# ---- Frontend assets -------------------------------------------------------
# Needs PHP + vendor because the @laravel/vite-plugin-wayfinder plugin
# regenerates route bindings with `php artisan wayfinder:generate` during
# `vite build`. Node arm64 does not run under QEMU, so this stage runs on the
# build platform; Vite output is static and platform-independent.
FROM --platform=$BUILDPLATFORM dunglas/frankenphp:1.12.7-php8.5-bookworm AS assets
ENV NODE_VERSION=v24.19.0
RUN apt-get update \
    && apt-get install -y --no-install-recommends curl ca-certificates xz-utils \
    && curl -fsSL "https://nodejs.org/dist/${NODE_VERSION}/node-${NODE_VERSION}-linux-x64.tar.xz" \
       | tar -xJ -C /usr/local --strip-components=1 \
    && rm -rf /var/lib/apt/lists/*
WORKDIR /app
COPY --from=vendor /app /app
RUN npm ci
RUN npm run build

# ---- Runtime ---------------------------------------------------------------
FROM dunglas/frankenphp:1.12.7-php8.5-bookworm
RUN install-php-extensions pdo_mysql redis intl zip opcache bcmath pcntl exif \
    && apt-get update \
    && apt-get install -y --no-install-recommends curl \
    && rm -rf /var/lib/apt/lists/*

ENV APP_ENV=production \
    PHP_OPCACHE_ENABLE=1 \
    PHP_OPCACHE_REVALIDATE_FREQ=0 \
    PHP_OPCACHE_VALIDATE_TIMESTAMPS=0 \
    PHP_OPCACHE_MAX_ACCELERATED_FILES=20000

WORKDIR /app
COPY --from=vendor /app /app
COPY --from=assets /app/public/build /app/public/build

# Writable runtime dirs; the storage volume is mounted over /app/storage.
RUN mkdir -p storage/app/public storage/framework/cache/data \
    storage/framework/sessions storage/framework/views storage/logs \
    && chown -R www-data:www-data storage bootstrap/cache public \
    && mkdir -p /etc/caddy /certs

COPY Caddyfile /etc/caddy/Caddyfile

EXPOSE 80 443
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
