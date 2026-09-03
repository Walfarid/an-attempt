---
paths:
  - Dockerfile
---

# Dockerfile

## PHP pinned to 8.4 — do not bump blindly
Carbon 3.13.x breaks under PHP 8.5 (`ArgumentCountError` in `rawCreateFromFormat`). Move to 8.5 only after Carbon 3.14+ lands. The base tag `dunglas/frankenphp:1.12.7-php8.4-bookworm` encodes this pin; updating it requires verifying Carbon compatibility first.

## `--platform=$BUILDPLATFORM` on vendor + assets stages
The CI build is multi-arch (amd64 + arm64) but the VM runs arm64. Node arm64 binaries do not run under QEMU, and PHP vendor output is platform-independent — both cross-compile stages pin `$BUILDPLATFORM` so they execute natively on the amd64 builder. The runtime stage intentionally omits it (it must match the target arch).

## `octane:install` regenerates `public/frankenphp-worker.php`
That file is gitignored; the build must recreate it via `php artisan octane:install --server=frankenphp`. The storage framework dirs must also exist before artisan boots (cache path required). Both happen in the vendor stage.

## PHP env vars (`PHP_OPCACHE_*`) are silently ignored
The FrankenPHP image does not translate `PHP_OPCACHE_*` env vars into ini directives — they land in `$_ENV` only. Real opcache tuning lives in `docker/zz-opcache.ini`, copied into `/usr/local/etc/php/conf.d/`. Same for upload limits in `docker/zz-upload.ini`.

## Caddyfile is mounted at runtime, not baked in
The COPY of `Caddyfile` in the Dockerfile is a fallback default. The VM mounts `/opt/walfa/Caddyfile` over `/etc/caddy/Caddyfile`, so Caddy config edits do not require an image rebuild.

## Composer pinned by digest
`COPY --from=composer@sha256:...` pins the exact Composer binary, not just a tag. Update the digest when bumping; verify the hash against Docker Hub.

## Layer ordering matters for cache efficiency
`composer.json`/`composer.lock` are COPYed before the rest of the app so `composer install` is cached when only source files change. Same pattern for `package.json`/`package-lock.json` (via full `/app` copy, but the npm cache mount helps).

## `resolve_root_symlink` in Caddyfile required by FrankenPHP
FrankenPHP symlinks the working directory; without `resolve_root_symlink` in the `php_server` block, PHP cannot resolve file paths correctly.
