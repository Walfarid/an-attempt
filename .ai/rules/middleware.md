---
paths:
  - app/Http/Middleware/TrackPageView.php
  - app/Http/Middleware/CachePublicResponses.php
  - app/Http/Middleware/HandleInertiaRequests.php
  - app/Http/Middleware/HandleConsent.php
  - app/Http/Middleware/SecurityHeaders.php
---

# Middleware

## Pageview tracking excludes Inertia background requests
TrackPageView::shouldTrack returns false for partial reloads (X-Inertia-Partial-Component header) and prefetches ($request->prefetch(), i.e. the client's `Purpose: prefetch` header). Both are background data fetches — counting them double-counted every homepage visit (initial + deferred fetch) and invented views on link hover. Plain X-Inertia XHR navigations ARE still tracked (real page views). Pinned by tests/Feature/TrackPageViewTest.php.

## Public page HTTP caching via middleware
CachePublicResponses middleware adds Cache-Control headers only for unauthenticated visitors. Controllers set `last_modified` as a request attribute (CarbonImmutable) when the content has a known modification time; the middleware promotes it to a Last-Modified response header. This works around Inertia Response not having its own headers. BlogController::show and GuideController::show both handle conditional requests (If-Modified-Since → 304) before the Inertia render. All public show() controllers that render full Inertia payloads must do conditional 304 checks + set the last_modified request attribute.

## Conditional shared props for auth-only data
Shared Inertia props that are only consumed by dashboard/settings layouts (like `sidebarOpen`) should be conditionally included only when `$request->user() !== null`. Public pages never read them. The TypeScript type must be optional (`sidebarOpen?: boolean`) and the consuming component must default (`?? true`).

## Consent-gated analytics markup: keep pages must-revalidate
Middleware emits public, max-age=60, stale-while-revalidate=300, must-revalidate for guests. must-revalidate + SWR means an expired cached page is never reused stale when it could be revalidated — a consent cookie change (decline→accepted or vice versa) must produce the matching analytics markup on the next navigation. Without must-revalidate, CDN/browser could replay a stale analytics variant to a user who declined, causing the beacon/clarity console errors. The @fonts inline <style> in app.blade.php is not covered by the Vite preload dedup, so font preloads duplicate per page.

## Consent middleware uses simple consent cookie for Google Consent Mode v2
HandleConsent reads the `consent` cookie (accepted | declined) to gate analytics (Clarity, GA4) and AdSense scripts. The cookie is set by the client-side Vue banner. On the Blade side, `app.blade.php` sets Google Consent Mode v2 defaults to `denied` on every page, and updates to `granted` only when `$consent === 'accepted'`. This makes the site AdSense-ready: when the user sets `ADSENSE_CLIENT_ID` in `.env`, the AdSense script loads behind the same consent gate.

## SecurityHeaders sets headers before $next so error responses are covered
SecurityHeaders must set headers BEFORE $next($request) so exception-rendered responses (404/500 via the exception handler) still carry them — the old after-$next code silently dropped headers on error pages. Keep the HSTS production check. The SecurityHeadersTest.extended 404 test guards this — do not regress to after-next-only.

## SecurityHeaders is global middleware — never move it back to the web group
CORRECTION to the previous rule: SecurityHeaders is registered as GLOBAL middleware (bootstrap/app.php $middleware->append), NOT in the web group — web-group middleware only runs on matched routes, so unmatched-route 404s bypassed it and error pages shipped without security headers. The after-$next header code is correct as-is; the registration was the bug. Keep it global and keep the after-$next response-header setting (setting headers on $request->headers does NOT propagate to the response). SecurityHeadersTest covers error responses.
