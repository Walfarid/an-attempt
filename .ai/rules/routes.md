---
paths:
  - routes/web.php
---

# Routes

## Dashboard browser audits: authenticate via forged redis session under APP_ENV=testing
ValidateSessionWithWorkOS skips validation when app()->runningUnitTests(), so serve a throwaway instance with APP_ENV=testing (same .env; note it uses the sqlite DB, so ensure a users row exists) and forge a session: sid = Str::random(40); store->put('login_web_'.sha1(SessionGuard::class), $userId) into redis via tinker (session driver is redis). Cookie value must be encrypter->encrypt(CookieValuePrefix::create('laravel-session', key).$sid, false) — EncryptCookies strips the prefix after decrypt and silently nulls cookies without it. Serve with SESSION_HTTP_ONLY=false (e.g. php -S from public/ using vendor/.../Foundation/resources/server.php) so the cookie is settable via document.cookie.
