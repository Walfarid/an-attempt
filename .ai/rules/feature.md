---
paths:
  - tests/Feature/AnalyticsTest.php
---

# Feature

## Click endpoint tests isolate the 60/min throttle; boundaries are inclusive
The analytics clicks endpoint (/analytics/clicks, throttle:60,1) has bound-tested validation: path max 500, element/label max 200 (boundaries inclusive). When testing the endpoint, isolate the rate limiter: boundary tests run $this->withoutMiddleware(ThrottleRequests::class); the rate-limit test does Cache::flush() then sends 60 real requests (assertNoContent) + a 61st asserting 429. Never stub the limiter — prove the route enforces it.
