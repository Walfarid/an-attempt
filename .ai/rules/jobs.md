---
paths:
  - app/Jobs/RecordPageView.php
  - app/Jobs/RecordClick.php
---

# Jobs

## RecordPageView silently swallows all exceptions — this is intentional
`handle()` wraps `PageView::create()` in a try/catch that logs to `Log::debug` and never rethrows. A dead queue or DB hiccup must never break a page response. The consequence: the job always "succeeds" from Laravel's perspective, so `$tries`, `$backoff`, and failed_jobs never engage — even though the prod worker runs with `--tries=3`. Do not remove the try/catch, and do not add retry/tries properties expecting them to fire. Pinned by tests/Feature/TrackPageViewTest.php.

## Queue name is hardcoded to `analytics`; connection is the app default
The constructor calls `$this->onQueue('analytics')`. No connection is set — it runs on whatever `QUEUE_CONNECTION` resolves to (currently `database`). The prod `queue` compose service consumes this named queue. Do not add `$connection = 'redis'` or change the queue name without updating compose.prod.yaml and the worker command in lockstep.

## No idempotency guard — safe only because the job is trivial
There is no unique constraint or dedup check. A duplicate would appear only if the driver redelivers before the worker acks (i.e., the job runs longer than `retry_after`). The current `PageView::create()` is a single INSERT, far under the 90s `retry_after`, so this has never fired. If you add heavier logic or external calls, add a unique key on `(path, viewed_at)` or a lock, or the retries you just enabled will create duplicate rows.

## `$data` shape is unvalidated beyond the PHPDoc
The constructor takes `array{path, ip, user_agent, referrer, user_id, viewed_at?}` — there is no runtime validation. The only dispatch site is `TrackPageView::terminate()`, which always passes the correct keys (including `viewed_at` — the job falls back to `now()` only for in-flight payloads). If you add a second dispatch site, pass the exact same shape or the INSERT will throw a missing-key error that the catch block will silently log and discard.

## Click writes are queued via RecordClick, never inline
RecordClick mirrors RecordPageView: ShouldQueue on `analytics` queue, try/catch + Log::debug that never rethrows, payload array{path, element, label, ip, user_agent, user_id, clicked_at?}. AnalyticsController::storeClick dispatches it instead of Click::create() inline so the Octane worker never blocks on a synchronous INSERT. Pinned by tests/Feature/AnalyticsTest.php (Queue::fake + assertPushed).

## Timestamps are captured at dispatch time, not worker handle time
Both jobs read `viewed_at` / `clicked_at` from the payload with a `?? now()` fallback for in-flight payloads queued before the change. Dispatch sites (`TrackPageView::terminate`, `AnalyticsController::storeClick`) always pass `now()` so the timestamp bins to the request's second — under queue lag, a worker-time `now()` would bin into the wrong day. Pinned by tests/Feature/TrackPageViewTest.php + tests/Feature/AnalyticsTest.php using `$this->travelTo()` + `$this->travel(1)->days()`.

## Column widths are enforced at dispatch time, not only in validation
`page_views.path`, `page_views.user_agent`, `page_views.referrer`, and `clicks.path`, `clicks.user_agent` are all VARCHAR(255). The dispatch sites truncate user_agent and referrer with `mb_substr($s, 0, 255)` before dispatch, and `AnalyticsController::storeClick` validates `path` at max:255 (matching the column, not the historical max:500). A MariaDB strict-mode INSERT silently truncates or errors when a value exceeds the column — the catch block would log it and drop the row. Pinned by tests/Feature/TrackPageViewTest.php (500-char user agent → 255 in DB) and tests/Feature/AnalyticsTest.php (255-char path persists via assertDatabaseHas, 500-char user agent → 255).

## IPs are anonymized at dispatch time; the payload contains a digest, not the address
Both dispatch sites (`TrackPageView::terminate`, `AnalyticsController::storeClick`) call `App\Support\Analytics::anonymizeIp($request->ip())` before building the payload — the `ip` field in `$data` is an HMAC-SHA256 digest keyed with the app key, never the raw address. The jobs insert that digest into `page_views.ip` / `clicks.ip` as-is (VARCHAR(64)). Pinned by tests/Feature/TrackPageViewTest.php and tests/Feature/AnalyticsTest.php (`assertDatabaseHas` with the expected digest and `assertDatabaseMissing` for the raw address).
