---
paths:
  - config/app.php
  - config/auth.php
  - config/contact.php
  - config/database.php
  - config/inertia.php
  - config/logging.php
  - config/mail.php
  - config/octane.php
  - config/session.php
---

# Config Files

## Octane: FrankenPHP, listeners are load-bearing
Server is `frankenphp` (not RoadRunner/Swoole). `DisconnectFromDatabases` and `CollectGarbage` are **intentionally commented out** in `OperationTerminated` — enabling them would drop the DB connection after every request. Do not uncomment. GC threshold 50 MB, max execution time 30 s.

## Inertia: SSR off, page-existence checks on
`ssr.enabled` is `false`. Do not flip to `true` without also setting the `bundle` path and provisioning an SSR process. `testing.ensure_pages_exist` is `true` — tests fail (not warn) when a referenced Vue component file is missing.

## Database: sqlite default, OCI SSL gotcha
Default connection is `sqlite` (overridden to MariaDB via `DB_URL` in prod). The `mysql` and `mariadb` connections carry a custom `ATTR_SSL_VERIFY_SERVER_CERT` block because Oracle Cloud's managed MySQL certificate has no SAN — hostname verification cannot pass against the private IP. Do not simplify that options block away.

## Redis: phpredis only, persistent connections, cache on DB 1
`REDIS_CLIENT` must stay `phpredis` (not predis). `REDIS_PERSISTENT=true` in dev. Cache uses a separate Redis database (DB 1) from the default connection (DB 0) — the prefix `laravel-database-` is auto-applied by phpRedis. The redis config carries `max_retries` + decorrelated-jitter backoff; do not strip those.

## Session: json serialization, not php
`serialization` is hardcoded `'json'`. Switching to `'php'` opens gadget-chain deserialization attacks if `APP_KEY` leaks. Cookie defaults: `http_only=true`, `same_site=lax`. `secure` defaults to `env('APP_ENV') === 'production'` — the cookie is flagged Secure in production automatically, overridable via `SESSION_SECURE_COOKIE` env (set `false` for dev over HTTP). Uses `env('APP_ENV')` rather than `app()->isProduction()` because config files load before the container `env` binding is available (Larastan bootstrap relies on this). Config default driver is `database` but `.env` overrides to `redis` — keep the `env()` call.

## Auth: single web guard, WorkOS handles the rest
Only one guard (`web`/session) and one provider (Eloquent `User::class`). No API guard. WorkOS SSO is the auth mechanism; do not add a second guard or provider without a clear reason. Password reset is 60 min / 60 s throttle — adequate for a single-admin site.

## Filesystems: media disk throws, CDN split
The `media` disk has `throw: true` (all others have `false`) — S3 errors propagate as exceptions. `MEDIA_URL` serves reads (CDN in prod, Garage in dev) while `AWS_ENDPOINT` receives writes — do not collapse them into one. Local disk root is `storage/app/private`, not the typical `storage/app`.

## Mail: log default in dev, port 2525 for Mailpit
Default mailer is `log` (not `smtp`). Dev SMTP port is `2525` (Mailpit in Docker). Production must override `MAIL_MAILER` via `.env`.

## Logging: deprecations silenced, stack is configurable
Deprecations channel is `null` (silenced) — intentional. Stack channel reads `LOG_STACK` env (defaults to `single`). Monthly channel caps at 3 files.

## App: UTC timezone is hardcoded, AES-256-CBC is fixed
Timezone is `'UTC'` — not env-driven. Do not change cipher from `AES-256-CBC` (would invalidate all encrypted data). `previous_keys` array supports `php artisan key:rotate` — keep the `explode`/`array_filter` pattern.

## Contact: falls through to MAIL_FROM_ADDRESS
`notification_email` cascades: `CONTACT_NOTIFICATION_EMAIL` → `MAIL_FROM_ADDRESS` → `'hello@example.com'`. Keep the fallback chain; dashboard contact form reads this.
