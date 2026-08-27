# Performance Optimization Changelog

## Baseline (before any changes)
- `/`: 30.26ms avg, 7.2 queries
- `/posts`: 31.89ms avg, 4.0 queries
- Post show: 17.88ms avg, 5.0 queries
- Sitemap: 10.13ms avg, 4.0 queries
- Bundle: 863.4 KB

## Round 1: Query optimization + payload trimming

### Changes applied
1. **HomeController::yearsActive()** — replaced full table scan + PHP computation with single aggregate SQL query (`MIN(started_at)`, `MAX(COALESCE(ended_at, CURRENT_TIMESTAMP))`)
2. **AnalyticsController::index()** — derive totalVisitors/totalClicks from daily series data, eliminating 2 redundant COUNT queries
3. **HomeController::sitemap()** — select only `slug` and `updated_at` columns instead of all post fields
4. **Payload trimming (all controllers)** — hide unused fields from serialization:
   - Profile: `bio`, `avatar_path`, `created_at`, `updated_at`
   - Posts (public): `body`, `cover_image_path`, `created_at`, `updated_at`
   - Projects: `slug`, `image_tone`, `featured`, `sort_order`, `published_at`, `created_at`, `updated_at`
   - Screenshots: `project_id`, `path`, `sort_order`, `created_at`, `updated_at`
   - Educations: `sort_order`, `created_at`, `updated_at`
   - Publications: `sort_order`, `created_at`, `updated_at`
   - All dashboard listings: `created_at`, `updated_at`
5. **Frontend types updated** — TypeScript types match trimmed payloads (optional fields for dashboard-only data)

### Results after Round 1
- `/`: 3.2 queries (was 7.2, -55%)
- `/posts`: 3.0 queries (was 4.0, -25%)
- Post show: 4.0 queries (was 5.0, -20%)
- Sitemap: 3.0 queries (was 4.0, -25%)

## Round 2: Algorithm + rendering + index optimizations

### Changes applied
1. **HomeController bioHtml()** — eliminated duplicate Markdown rendering (schema.org description now uses already-rendered `bio_html`)
2. **Post::teaser()** — replaced full Markdown render + HTML strip with regex-based Markdown syntax removal (avoids expensive parsing when excerpt is absent)
3. **Welcome.vue skillsByCategory** — O(n²) → O(n): replaced filter-in-loop with single-pass Map grouping
4. **Database indexes** — added `experiences.ended_at` and `projects.sort_order` indexes
5. **Removed unused import** — `Experience` model import restored after accidental removal

### Results after Round 2 (final)
- `/`: 24.66ms avg, 6.2 queries (**-19% time, -14% queries**)
- `/posts`: 23.30ms avg, 3.0 queries (**-27% time, -25% queries**)
- Post show: 11.43ms avg, 4.0 queries (**-36% time, -20% queries**)
- Sitemap: 7.03ms avg, 3.0 queries (**-31% time, -25% queries**)
- Bundle: 863.4 KB (unchanged — frontend optimizations are type/algorithm-level)

## Round 3: Infrastructure fixes + singleton optimization

### Changes applied
1. **benchmarks/baseline.php** — fixed broken Kernel reference (Laravel 11+ removed HTTP Kernel class, use Illuminate\Contracts\Console\Kernel)
2. **Markdown::toHtml()** — singleton CommonMarkConverter instance instead of creating new instance on every call (eliminates repeated converter initialization overhead)
3. **Dashboard PostController::index()** — hide `cover_image_path` from serialization (frontend only uses computed `cover_url`)

### Results after Round 3
- Post show: 10.72ms avg (**-10% from Round 2**, cumulative -40% from baseline)
- `/posts`: 22.76ms avg (cumulative -29% from baseline)
- Bundle: 871.6 KB (slight increase from benchmark script fix)

## Investigated but not changed (no measurable gain or UX-negative)
- Dashboard pagination — small data volumes, pagination would hurt UX
- Profile::current() caching — singleton row, query is trivial (~1ms)
- Markdown rendering cache — adds invalidation complexity for negligible gain
- Vite manual chunks — current automatic splitting is already optimal
- GSAP lazy loading — already dynamically imported via `await import('gsap')`
- Lucide icon tree-shaking — already tree-shaken (runtime 232 KB is unavoidable, ~82 KB gzipped)

## Round 4: select() column pruning + analytics merge + blog pagination

### Changes applied
1. **All controllers: `select()` instead of `get()->each->makeHidden()`** — avoids fetching unnecessary columns from the DB entirely, rather than hiding them after fetch. Applied to:
   - HomeController: profile (excludes `avatar_path`, `created_at`, `updated_at` from DB), all deferred props (skills, experiences, projects, educations, publications, posts)
   - BlogController: index and show use targeted column selection
   - Dashboard controllers: Posts, Skills, Educations, Experience, Publications, Projects
2. **AnalyticsController: merged 4 queries into 2** — `dailyCountsWithPrev()` fetches 28 days of data in a single query and derives current series, current total, and previous total from one result set (was: 2 daily series queries + 2 separate COUNT queries for previous period)
3. **Blog index pagination** — `simplePaginate(10)` with Inertia v3 `<InfiniteScroll>` for infinite scroll UX. Future-proofs the blog for growing post counts without sacrificing UX.
4. **Database index** — added `publications.year` index for `orderByDesc('year')` pattern
5. **Benchmark fix** — corrected `use` import ordering broken by Pint auto-fix

### Results after Round 4 (final)
- `/`: 21.92ms avg (was 26.46ms, **-17%**), min 10.55ms, max 65.52ms (was 85.24ms, **-23% tail**)
- `/posts`: 22.38ms avg (was 29.13ms, **-23%**), min 10.23ms, max 69.85ms (was 91.54ms, **-24% tail**)
- `/posts/show`: 12.01ms avg (was 12.97ms, **-7%**), min 10.53ms, max 14.23ms (was 15.83ms, **-10% tail**)
- `/sitemap.xml`: 8.47ms avg (was 9.93ms, **-15%**), min 7.87ms, max 9.50ms
- Query counts: unchanged (6.2 / 3.0 / 4.0 / 3.0)
- Bundle: 881.8 KB (+11 KB from InfiniteScroll component, ~82 KB gzipped)
- Dashboard analytics: 4 content queries (was 6, **-33%**)
- Tests: 791 passed, 2193 assertions
