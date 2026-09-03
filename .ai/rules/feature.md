---
paths:
  - tests/Feature/AnalyticsTest.php
---

# Feature

## Click endpoint tests isolate the 60/min throttle; boundaries are inclusive
The analytics clicks endpoint (/analytics/clicks, throttle:60,1) has bound-tested validation: path max 500, element/label max 200 (boundaries inclusive). When testing the endpoint, isolate the rate limiter: boundary tests run $this->withoutMiddleware(ThrottleRequests::class); the rate-limit test does Cache::flush() then sends 60 real requests (assertNoContent) + a 61st asserting 429. Never stub the limiter — prove the route enforces it.

## Guest test pins anonymous 204 + DB insert
A guest test posts to /analytics/clicks with no auth and no WorkOS session and asserts a 204 (assertNoContent) plus the inserted Click row (user_id nullable, not set). This pins that the endpoint stays public and writes anonymous rows — reverting it back behind auth/session breaks both this assertion and the dashboard CTR that aggregates the clicks. Keep the guest path covered whenever these analytics routes change.
