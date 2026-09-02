---
paths:
  - app/Http/Controllers/BlogController.php
  - app/Http/Controllers/HomeController.php
  - 'app/Http/Controllers/HomeController.php, routes/web.php'
---

# Controllers

## Post show fetches post + recent in one driver-aware query
The "Keep reading" list is inlined into the post SELECT as a JSON aggregate (recentSql()). SQLite lacks JSON_ARRAYAGG and treats double-quoted JSON labels as identifiers, so the helper branches per driver (json_group_array vs JSON_ARRAYAGG+COALESCE) and always uses single-quoted labels. The route is posts/{post} with a manual slug lookup (no model binding — that would add a second query); keep the param name `post` or regenerate Wayfinder. Local dev is SQLite, tests/CI run MariaDB — both branches are covered by the suite.

## Public project skills ship id+name only (category is dashboard-only)
The homepage deferred projects eager load selects ['skills.id','skills.name'] — Welcome.vue renders project tags from name (id as key) and never reads skill.category. Category lives only on the standalone `skills` prop (grouping UI). Keep the two datasets' shapes distinct; the TS Skill type marks category optional. Extra columns would be dead wire bytes (~22 B/row).

## Project skills: drop the BelongsToMany pivot from the wire
Both HomeController deferred projects and Dashboard\ProjectController::index() select skills id+name but Eloquent still serializes pivot columns (~37 B/row dead wire). After each(), rebuild the relation with $project->setRelation('skills', $project->skills->map->only(['id','name'])) — frontend renders name only and reads ids from the standalone skills prop. Keeps TS Skill {id,name,category?} honest and stays off any skills.* pivot accessor.

## Eager home props are closures; head block skipped on partial reloads
HomeController::index resolves the profile via a memoized closure shared by the `profile` and `stats` props, and wraps the Head:: calls in `! $request->hasHeader(Header::PARTIAL_COMPONENT)`. Partial reloads (deferred groups) never receive profile/stats/head — computing them was a discarded query + Markdown render per background request (deferred went 9 → 8 queries). Also: public payloads ship no unused IDs — profile select has no `id`, screenshots hide `id` (dashboard re-adds it via DashboardProject in Projects.vue), privacy policy hides `id`. Frontend types mirror the wire exactly.

## BelongsToMany eager loads need the parent FK in select([...])
Eager loading tags (belongsToMany via post_tag) silently returns [] when the parent query uses select([...]) without posts.id — pivot matching has no parent key to join on. Any public query adding ->with('tags') over a column subset MUST include posts.id, then hide it (makeHidden('id')) to keep the no-unused-IDs public-wire rule for single-post payloads (list payloads already ship id for v-for keys). Symptom: keywords/keywords pills empty in tests/JSON-LD while tinker shows the relation fine.

## Home meta description years derives from experiences aggregate
Head-derived content (meta descriptions) should pull numbers from the same aggregate source the page renders: HomeController derives "X years of experience" from the experiences aggregate (MIN(started_at)/MAX(ended_at)) via stats(), not a hardcoded string — Hedberg-style drift (bio says 6 years, table says 4) is a rounding trap. Rounding rule: months/12 rounded up to whole years (max(1, ...))- matching hero stats.

## Sitemap: guides and tags are part of it; keep them in
HomeController::sitemap must include the guides index, every published guide (Guide::published(), lastmod from updated_at), and tag pages of tags on published posts (Tag::used() — empty tags are soft-404s). Never remove guides/tags from the sitemap when adding a public content type; extend it instead (same pattern: listing + per-item URLs, priorities per type, Last-Modified header from max of all fetched updated_at). Pinned by tests/Feature/SitemapTest.php.

## Profile name is cached; do not query it per request
The post-show hot path reads the author name via Cache::remember('profile.name', now()->addHour(), ...). Never replace it with a direct Profile::query()->value() — a DB query per post view defeats the cache. If the underlying model or key changes, keep the Cache wrapper and invalidate the key from wherever Profile is written (currently Dashboard ProfileController::update does Cache::forget('profile.name')).

## Sitemap: cached XML, 304 handling, and key invalidation contract
sitemap() must keep its zero-query cache-hit path: it checks Cache::get('sitemap.xml') before running queries, serves 304 on fresh If-Modified-Since (isNotModified), and stores rendered XML + last_modified for 1h. Never remove the CachePublicResponses middleware from the sitemap route (routes/web.php) — it promotes last_modified to the Last-Modified header. Whenever a Dashboard write changes posts, guides, tags (via posts), or privacy policy, it MUST Cache::forget('sitemap.xml') and Cache::forget('sitemap.last_modified') (currently in Post/Guide/PrivacyPolicy dashboard controllers). New content types added to the sitemap must also update the last_modified cache entry.
