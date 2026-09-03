---
paths:
  - 'app/Support/SitemapCache.php'
  - 'app/Http/Controllers/HomeController.php'
  - 'tests/Feature/SitemapTest.php'
---

# Sitemap Cache

## Two keys, one invalidation

SitemapCache owns exactly two cache keys: `XML` (rendered sitemap string) and `LAST_MODIFIED` (Carbon timestamp). They must be stored and flushed together — a hit on XML without LAST_MODIFIED still serves the page, but a hit on LAST_MODIFIED without XML is dead data. `invalidate()` forgets both; never call `Cache::forget` on either key directly.

## No cache tags

The project cache driver does not use tags. Invalidation is explicit: every dashboard mutation that changes public URLs or modified timestamps must call `SitemapCache::invalidate()`. Current callers: PostController (store/update/destroy), GuideController (store/update/destroy), PrivacyPolicyController (update). Adding a new content type to the sitemap builder in HomeController::sitemap requires adding invalidation in that type's dashboard controller too.

## TTL and HTTP caching

- Cache TTL: `now()->addHour()` for both keys.
- Response header: `Cache-Control: public, max-age=3600`.
- `CachePublicResponses` middleware on the sitemap route promotes `last_modified` to the `Last-Modified` response header.
- Conditional requests: `isNotModified()` compares `If-Modified-Since` (second precision) against the cached timestamp and returns 304 when fresh.

## Zero-query cache-hit path

On a cache hit the sitemap method returns the cached XML without running any Post/Guide/Tag/PrivacyPolicy queries. This is intentional — the whole point of the cache is to skip those four queries. Do not add queries before the cache check.

## Last-Modified derivation

`LAST_MODIFIED` is `max(posts.updated_at, guides.updated_at, privacy.updated_at)` — computed from already-fetched collections, no extra query. When adding a new content type to the sitemap, include its `updated_at` in this max or the cache will serve a stale Last-Modified header until TTL expires.
