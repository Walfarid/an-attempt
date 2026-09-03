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
The constructor takes `array{path, ip, user_agent, referrer, user_id}` — there is no runtime validation. The only dispatch site is `TrackPageView::terminate()`, which always passes the correct keys. If you add a second dispatch site, pass the exact same shape or the INSERT will throw a missing-key error that the catch block will silently log and discard.

## Click writes are queued via RecordClick, never inline
RecordClick mirrors RecordPageView: ShouldQueue on `analytics` queue, try/catch + Log::debug that never rethrows, payload array{path, element, label, ip, user_agent, user_id}. AnalyticsController::storeClick dispatches it instead of Click::create() inline so the Octane worker never blocks on a synchronous INSERT. Pinned by tests/Feature/AnalyticsTest.php (Queue::fake + assertPushed).
