---
paths:
  - bootstrap/app.php
---

# Bootstrap / App Configuration

## Cookie encryption exceptions are load-bearing
`encryptCookies(except: ['appearance', 'sidebar_state', 'consent'])` — these three cookies are read by client-side JavaScript (Vue appearance toggle, sidebar persistence, consent banner). Encrypting them makes them opaque to JS and silently breaks all three features. Never remove an entry from the `except` list without verifying the consumer is server-side only.

## SecurityHeaders is global, not web-group
`$middleware->append(SecurityHeaders::class)` registers it as **global** middleware. Using `$middleware->web(append:)` would skip unmatched routes, so 404/exception responses would ship without security headers. This is intentional — SecurityHeaders sets headers after `$next($request)` on `$response->headers`, which requires the middleware to run on every response.

## Web middleware append order matters
The five middleware in `$middleware->web(append:)` execute in listed order:
1. `HandleAppearance` — shares appearance into Blade views
2. `HandleConsent` — resolves consent state from cookie
3. `HandleInertiaRequests` — builds shared Inertia props (reads consent cookie for AdSense, reads auth for sidebar)
4. `AddLinkHeadersForPreloadedAssets` — adds Link headers for Vite preloads
5. `TrackPageView` — dispatches analytics job in `terminate()`

HandleAppearance and HandleConsent must precede HandleInertiaRequests because Inertia's `share()` reads the consent cookie and view-shared appearance value. Moving TrackPageView before Inertia would run analytics before the response is finalized. Do not reorder without verifying dependencies.

## JSON exception rendering covers both API-prefix and content-negotiated requests
`shouldRenderJsonWhen` returns true for `$request->is('api/*') || $request->expectsJson()`. Even though there's no dedicated API route group (per http-conventions), this guard ensures that any route receiving an `Accept: application/json` header (e.g. prefetch, HTMX-style calls) gets a JSON error response instead of an HTML error page.

## No custom service provider registration
The `->create()` call uses auto-discovery only. Do not add `->withProviders()` — Laravel auto-discovers providers from `bootstrap/providers.php`, and explicit registration here would risk double-booting.
