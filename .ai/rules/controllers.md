---
paths:
  - app/Http/Controllers/BlogController.php
  - app/Http/Controllers/HomeController.php
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
