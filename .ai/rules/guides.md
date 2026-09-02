---
paths:
  - app/Models/Guide.php
  - app/Http/Controllers/Dashboard/GuideController.php
  - app/Http/Controllers/Dashboard/GuideCoverController.php
  - app/Http/Controllers/GuideController.php
  - app/Http/Requests/Dashboard/GuideRequest.php
  - app/Http/Requests/Dashboard/UploadGuideCoverRequest.php
  - resources/js/pages/dashboard/Guides.vue
  - 'resources/js/pages/guides/**'
  - 'app/Http/Controllers/Dashboard/**'
  - app/Http/Controllers/Dashboard/AnalyticsController.php
---

# Guides

Guides are tutorial-style content with step-by-step markdown body, prerequisites, and estimated time. They parallel posts but are a separate content type with bidirectional cross-references (guide ↔ post via `guide_post` pivot).

## Content model
- Slugs are the public identifier, integer IDs internal-only.
- Nullable `published_at` = publish model (NULL = draft, future date = scheduled).
- Cover images store paths on the `media` disk, never absolute URLs.
- Dashboard CRUD is dialog-style: index/show/store/update/destroy only. One Form Request per entity.
- Guide bodies are Markdown rendered via `App\Support\Markdown::toHtml()`. Step sections use `## Step N: Title` headings.

## Public pages
- `/guides` listing: paginated, newest first, published only.
- `/guides/{slug}` detail: manual slug lookup (no model binding), abort_unless published.
- Related posts shown on guide detail via `guide_post` pivot.

## Cross-references
- `Guide::posts()` ↔ `Post::guides()` via `guide_post` pivot (cascadeOnDelete both sides).
- Dashboard form includes a post multiselect (`PostPicker`) for linking; the store/update controller syncs `validated('posts', [])`.

## Bulk upsert BelongsToMany inputs instead of per-row firstOrCreate in controllers
syncTags-style methods that create tags/related rows from request input must not loop firstOrCreate: each iteration costs a SELECT+INSERT and races on slug uniqueness under concurrent writes. Resolve slugs against one whereIn('slug') query, insert missing rows with a single insertOrIgnore inside a DB::transaction, then one whereIn('name')->pluck('id') and one sync(). If a name is still missing after the insert (slug race with a different name), recreate it once with a suffixed slug. Keep tags.name and tags.slug unique. See PostController::syncTags (PostController.php) for the canonical implementation.

## Sitemap cache keys live in SitemapCache support class; controllers call invalidate()
Sitemap cache keys (sitemap.xml + sitemap.last_modified) are defined once in App\Support\SitemapCache as constants; that class owns both keys and the invalidate() that forgets them. Dashboard controllers must never Cache::forget sitemap keys directly — call SitemapCache::invalidate() after any content mutation that changes public URLs or modified timestamps (posts, guides, privacy policy). HomeController::sitemap reads/writes through the same constants. Adding a new content type: invalidate there too, and update the sitemap builder collection.

## Aggregate analytics clicks via one derived-table LEFT JOIN, not correlated selectSub
topPages aggregates click counts with ONE query: joinSub a derived table (SELECT path, count(*) as clicks FROM clicks WHERE clicked_at >= ? GROUP BY path) as c on c.path = page_views.path (left), then COALESCE(MAX(c.clicks), 0) as clicks — MAX is a no-op per unique joined group and satisfies ONLY_FULL_GROUP_BY. Never reintroduce selectSub here: a correlated subquery runs once per top-page row (5 subqueries per dashboard load). Keep the topPages prop shape (path, title, visitors, clicks) intact.
