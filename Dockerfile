# syntax=docker/dockerfile:1
# Production image for the walfa Laravel + Inertia app.
#
# Runtime: FrankenPHP (Caddy + PHP 8.4 in one binary) with Laravel Octane
# worker mode. Multi-arch build (linux/amd64 + linux/arm64) is done in CI via
# buildx; the VM is arm64.
#
# PHP pinned to 8.4: Carbon 3.13.2 (latest stable) breaks under PHP 8.5
# (ArgumentCountError in rawCreateFromFormat). Bump to 8.5 once Carbon 3.14+
# lands with PHP 8.5 support.
#
# Base images verified 2026-09-02 against Docker Hub:
#   dunglas/frankenphp:1.12.7-php8.4-bookworm
# Node tarball: v24.19.0 (matches local dev, Node 24 line used by CI).

# ---- PHP dependencies ------------------------------------------------------
# Runs on the build platform (amd64 in CI): PHP packages are
# platform-independent, so one build serves both target arches.
FROM --platform=$BUILDPLATFORM dunglas/frankenphp:1.12.7-php8.4-bookworm AS vendor
WORKDIR /app
# Composer image verified 2026-09-02 (Composer 2.10.3).
COPY --from=composer@sha256:8fa35f42911ff8bbee92aa37d781de6799168d4a0535ac6991f1b250bc2e0245 /usr/bin/composer /usr/bin/composer
RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip \
    && rm -rf /var/lib/apt/lists/*
COPY composer.json composer.lock ./
RUN --mount=type=cache,target=/root/.composer \
    composer install --no-dev --no-interaction --no-progress --no-scripts --prefer-dist
COPY . .
# Framework-writable dirs are gitignored, so recreate them before booting
# artisan (octane:install needs a valid cache path).
RUN mkdir -p storage/framework/cache/data storage/framework/sessions \
    storage/framework/views storage/logs
# octane:install drops the gitignored public/frankenphp-worker.php that the
# Caddyfile worker mode needs.
RUN php artisan octane:install --server=frankenphp --no-interaction \
    && php artisan package:discover --ansi \
    && composer dump-autoload --optimize --classmap-authoritative --no-interaction --no-scripts

# ---- Frontend assets -------------------------------------------------------
# Needs PHP + vendor because the @laravel/vite-plugin-wayfinder plugin
# regenerates route bindings with `php artisan wayfinder:generate` during
# `vite build`. Node arm64 does not run under QEMU, so this stage runs on the
# build platform; Vite output is static and platform-independent.
FROM --platform=$BUILDPLATFORM dunglas/frankenphp:1.12.7-php8.4-bookworm AS assets
ENV NODE_VERSION=v24.19.0
RUN apt-get update \
    && apt-get install -y --no-install-recommends curl ca-certificates xz-utils \
    && curl -fsSL "https://nodejs.org/dist/${NODE_VERSION}/node-${NODE_VERSION}-linux-x64.tar.xz" \
       | tar -xJ -C /usr/local --strip-components=1 \
    && rm -rf /var/lib/apt/lists/*
WORKDIR /app
COPY --from=vendor /app /app
RUN --mount=type=cache,target=/root/.npm \
    npm ci
RUN npm run build

# ---- Runtime ---------------------------------------------------------------
FROM dunglas/frankenphp:1.12.7-php8.4-bookworm
RUN install-php-extensions pdo_mysql redis intl zip opcache bcmath pcntl exif \
    && apt-get update \
    && apt-get install -y --no-install-recommends curl \
    && rm -rf /var/lib/apt/lists/*

ENV APP_ENV=production

# Real opcache config (the old PHP_OPCACHE_* env vars were never read by the
# image; see docker/zz-opcache.ini for the verification record).
COPY docker/zz-opcache.ini /usr/local/etc/php/conf.d/zz-opcache.ini
COPY docker/zz-upload.ini /usr/local/etc/php/conf.d/zz-upload.ini

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
