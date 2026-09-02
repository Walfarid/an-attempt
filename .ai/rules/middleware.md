---
paths:
  - app/Http/Middleware/TrackPageView.php
  - app/Http/Middleware/CachePublicResponses.php
  - app/Http/Middleware/HandleInertiaRequests.php
---

# Middleware

## Pageview tracking excludes Inertia background requests
TrackPageView::shouldTrack returns false for partial reloads (X-Inertia-Partial-Component header) and prefetches ($request->prefetch(), i.e. the client's `Purpose: prefetch` header). Both are background data fetches — counting them double-counted every homepage visit (initial + deferred fetch) and invented views on link hover. Plain X-Inertia XHR navigations ARE still tracked (real page views). Pinned by tests/Feature/TrackPageViewTest.php.

## Public page HTTP caching via middleware
CachePublicResponses middleware adds Cache-Control headers only for unauthenticated visitors. Controllers set `last_modified` as a request attribute (CarbonImmutable) when the content has a known modification time; the middleware promotes it to a Last-Modified response header. This works around Inertia Response not having its own headers. BlogController::show also handles conditional requests (If-Modified-Since → 304) before the Inertia render.

## Conditional shared props for auth-only data
Shared Inertia props that are only consumed by dashboard/settings layouts (like `sidebarOpen`) should be conditionally included only when `$request->user() !== null`. Public pages never read them. The TypeScript type must be optional (`sidebarOpen?: boolean`) and the consuming component must default (`?? true`).

## Consent-gated analytics markup: keep pages must-revalidate
Middleware emits public, max-age=60, stale-while-revalidate=300, must-revalidate for guests. must-revalidate + SWR means an expired cached page is never reused stale when it could be revalidated — a consent cookie change (decline→accepted or vice versa) must produce the matching analytics markup on the next navigation. Without must-revalidate, CDN/browser could replay a stale analytics variant to a user who declined, causing the beacon/clarity console errors. The @fonts inline <style> in app.blade.php is not covered by the Vite preload dedup, so font preloads duplicate per page.
