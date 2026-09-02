---
paths:
  - routes/web.php
---

# Routes

## Dashboard browser audits: authenticate via forged redis session under APP_ENV=testing
ValidateSessionWithWorkOS skips validation when app()->runningUnitTests(), so serve a throwaway instance with APP_ENV=testing (same .env; note it uses the sqlite DB, so ensure a users row exists) and forge a session: sid = Str::random(40); store->put('login_web_'.sha1(SessionGuard::class), $userId) into redis via tinker (session driver is redis). Cookie value must be encrypter->encrypt(CookieValuePrefix::create('laravel-session', key).$sid, false) — EncryptCookies strips the prefix after decrypt and silently nulls cookies without it. Serve with SESSION_HTTP_ONLY=false (e.g. php -S from public/ using vendor/.../Foundation/resources/server.php) so the cookie is settable via document.cookie.

## Dashboard browser audits: forged-session format on Laravel 12+
The old "forge a redis session" recipe is outdated for this Laravel version. Current facts (verified 2026-09): session cookie name is `laravel-session` (hyphen); cookie value prefix is hmac v2: `hash_hmac('sha1', 'laravel-sessionv2', APP_KEY).'|'` — build the cookie via `EncryptCookies::handle()` on a response cookie (round-trip guaranteed) rather than hand-rolling Crypt::encrypt; session payloads are stored as `serialize(json_encode($attrs))` (JSON session serialization) under redis key `laravel-cache-<sid>` on the DEFAULT redis connection (phpRedis auto-prefixes `laravel-database-`), NOT via Cache::store('redis') — that store maps to the separate `cache` connection and misses. Serve with APP_ENV=testing + SESSION_HTTP_ONLY=false.

## ads.txt route contract: config-driven content, 404 when unset, tested in AdsTxtTest
The /ads.txt route reads config('services.ads.txt') (env ADSTXT_CONTENT), serves it text/plain with a trailing newline behind CachePublicResponses, aborts 404 when empty. Tests live in tests/Feature/AdsTxtTest.php — keep the 200-with-content, 404-empty, and Cache-Control branches covered whenever this route changes (e.g. if content moves to storage or a controller).
