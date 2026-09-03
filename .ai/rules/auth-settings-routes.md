---
paths:
  - routes/auth.php
  - routes/settings.php
---

# Auth & Settings Routes

## Auth routes are WorkOS request-object closures, not controllers
`routes/auth.php` has no controller — each route closure type-hints a Laravel WorkOS request class (`AuthKitLoginRequest`, `AuthKitAuthenticationRequest`, `AuthKitLogoutRequest`) and delegates entirely to it. Do not refactor these into a controller; the WorkOS request objects handle the redirect-to-AuthKit-portal flow, callback validation, and session teardown internally.

## Authenticate uses a tap() redirect-then-authenticate pattern
The `authenticate` route wraps `redirect()->intended()` in `tap()` so the redirect response is built first and `$request->authenticate()` (which writes the session) runs in the `tap` callback. Reversing the order (authenticate first, then build the redirect) breaks because `authenticate()` may redirect to AuthKit itself on failure, and the `tap` closure would then run against the wrong response.

## Login/authenticate are guest-only with a strict throttle
Both `login` and `authenticate` sit behind `['guest', 'throttle:10,1']`. The throttle is 10 requests per 1 minute — tight on purpose because each hit bounces through the external AuthKit portal. `logout` is intentionally outside this group: it requires `auth` middleware (you must be logged in to log out).

## Settings routes use the WorkOS session validation middleware by class reference
`routes/settings.php` applies `ValidateSessionWithWorkOS::class` directly (not a string alias) alongside `auth`. This middleware re-validates the WorkOS session on every settings request. Any new settings sub-page must be inside this middleware group.

## Route::inertia() shortcut for zero-logic settings pages
`settings/appearance` uses `Route::inertia('settings/appearance', 'settings/Appearance')` instead of a controller. Use this pattern for any future settings page that only renders a Vue component with no server-side data — no controller needed.

## Bare /settings redirects to /settings/profile
`Route::redirect('settings', '/settings/profile')` ensures the settings URL always lands on a real page. Do not remove this or replace it with a controller render.

## ProfileController destroy uses AuthKitAccountDeletionRequest
Account deletion goes through `AuthKitAccountDeletionRequest`, not a local `FormRequest`. The `$request->delete(using: fn)` callback runs the actual user deletion after WorkOS confirms the intent. Do not swap this for a standard delete flow — WorkOS must confirm the deletion intent first.
