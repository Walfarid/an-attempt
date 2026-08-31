---
paths:
  - app/Http/Middleware/TrackPageView.php
---

# Middleware

## Pageview tracking excludes Inertia background requests
TrackPageView::shouldTrack returns false for partial reloads (X-Inertia-Partial-Component header) and prefetches ($request->prefetch(), i.e. the client's `Purpose: prefetch` header). Both are background data fetches — counting them double-counted every homepage visit (initial + deferred fetch) and invented views on link hover. Plain X-Inertia XHR navigations ARE still tracked (real page views). Pinned by tests/Feature/TrackPageViewTest.php.
