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
