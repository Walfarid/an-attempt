---
paths:
  - app/Http/Middleware/TrackPageView.php
  - app/Http/Middleware/CachePublicResponses.php
  - app/Http/Middleware/HandleInertiaRequests.php
  - app/Http/Middleware/HandleConsent.php
  - app/Http/Middleware/HandleAppearance.php
  - app/Http/Middleware/SecurityHeaders.php
---

# Middleware

## Pageview tracking excludes Inertia background requests
TrackPageView::shouldTrack returns false for partial reloads (X-Inertia-Partial-Component header) and prefetches ($request->prefetch(), i.e. the client's `Purpose: prefetch` header). Both are background data fetchs — counting them double-counted every homepage visit (initial + deferred fetch) and invented views on link hover. Plain X-Inertia XHR navigations ARE still tracked (real page views). Pinned by tests/Feature/TrackPageViewTest.php.

## Pageview tracking excludes machine-facing static resources
TrackPageView::shouldTrack returns false for favicon.ico, robots.txt, sitemap.xml, and ads.txt. These are fetched by crawlers, browsers, and ad verification bots — not real page views. Counting them would inflate analytics with bot traffic. Pinned by tests/Feature/TrackPageViewTest.php.

## Analytics writes are queued, never inline in terminate()
TrackPageView::terminate dispatches an `App\Jobs\RecordPageView` job instead of INSERTing a PageView synchronously — under Octane/FrankenPHP the worker is blocked until terminate() returns, so an inline write added a per-request round trip to every public page. The job carries the captured request fields and writes on the `analytics` queue (compose.prod.yaml runs a dedicated `queue` worker). Never revert terminate() to a direct PageView::create(); keep the try/catch + Log::debug fallback so a dead queue never breaks the response. Pinned by tests/Feature/TrackPageViewTest.php (Queue::fake + assertPushed).

## Public page HTTP caching via middleware
CachePublicResponses middleware adds Cache-Control headers only for unauthenticated visitors. Controllers set `last_modified` as a request attribute (CarbonImmutable) when the content has a known modification time; the middleware promotes it to a Last-Modified response header. This works around Inertia Response not having its own headers. BlogController::show, GuideController::show, and PrivacyController::show all handle conditional requests (If-Modified-Since → 304) before the Inertia render, each with a private `isNotModified(Request, CarbonInterface)` helper. All public show() controllers that render full Inertia payloads must do conditional 304 checks + set the last_modified request attribute. Index pages (posts/guides/tag) follow the same contract: compute `max(updated_at)` over the published rows, 304 when fresh (set the attribute as a Carbon instance — the middleware calls toRfc7231String()), never a raw string. Pinned by tests/Feature/CachePublicResponsesTest.php.

## Conditional shared props for auth-only data
Shared Inertia props that are only consumed by dashboard/settings layouts (like `sidebarOpen`) should be conditionally included only when `$request->user() !== null`. Public pages never read them. The TypeScript type must be optional (`sidebarOpen?: boolean`) and the consuming component must default (`?? true`).

## Consent-gated analytics markup: keep pages must-revalidate
Middleware emits public, max-age=60, stale-while-revalidate=300, must-revalidate for guests. must-revalidate + SWR means an expired cached page is never reused stale when it could be revalidated — a consent cookie change (decline→accepted or vice versa) must produce the matching analytics markup on the next navigation. Without must-revalidate, CDN/browser could replay a stale analytics variant to a user who declined, causing the beacon/clarity console errors. The @fonts inline <style> in app.blade.php is not covered by the Vite preload dedup, so font preloads duplicate per page.

## Consent middleware uses simple consent cookie for Google Consent Mode v2
HandleConsent reads the `consent` cookie (accepted | declined) to gate analytics (Clarity, GA4) and AdSense scripts. The cookie is set by the client-side Vue banner. On the Blade side, `app.blade.php` sets Google Consent Mode v2 defaults to `denied` on every page, and updates to `granted` only when `$consent === 'accepted'`. This makes the site AdSense-ready: when the user sets `ADSENSE_CLIENT_ID` in `.env`, the AdSense script loads behind the same consent gate.

## SecurityHeaders is global middleware and sets headers after $next on the response
SecurityHeaders is registered as GLOBAL middleware (`bootstrap/app.php` `$middleware->append`), not in the web group — web-group middleware only runs on matched routes, so unmatched-route 404s would bypass it and error pages would ship without security headers. Headers are set AFTER `$next($request)` on `$response->headers`, which is correct: every response that passes through the middleware (including exception-rendered 404/500 responses) carries them because the middleware is global. Setting headers on `$request->headers` would NOT propagate to the response. Keep the HSTS production check. SecurityHeadersTest covers error responses.
