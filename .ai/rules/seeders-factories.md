---
paths:
  - 'database/seeders/**'
  - 'database/factories/**'
---

# Seeders & Factories

## DatabaseSeeder is idempotent — re-running replaces data in place
Every `seed*()` method matches on natural keys (`updateOrCreate`/`firstOrCreate`) and then deletes orphans the resume no longer lists (`whereNotIn('slug', ...)->delete()`). Do not change this to `create()` — re-seeding would duplicate rows. The seeder is the resume's source of truth, not a one-shot loader.

## Seeder ordering: skills before projects
`seedSkills()` runs before `seedProjects()` because projects reference skills by name and sync them via `$model->skills()->sync($ids)`. `seedSkills()` returns a `name → id` map that `seedProjects()` consumes. Moving skills after projects breaks this.

## Profile is a singleton row (id = 1)
`seedProfile()` always targets `id = 1`; the app fetches it via `Profile::current()`. Never seed multiple profiles or make the profile row random.

## Factory slug/path uniqueness: instance memory + DB check
PostFactory, GuideFactory, TagFactory, and MediaFactory track used values in an instance array (`$usedSlugs`, `$usedNames`, `$usedPaths`) AND query the DB in `configure()`'s `afterMaking` callback. This two-layer approach catches collisions both within a `count(N)->create()` batch and across test runs. Do not replace with `fake()->unique()` alone — that resets between factory instances and fails on `count(N) > pool size`.

## `afterMaking` derives slugs from the final title
PostFactory/GuideFactory set the slug in `configure()` → `afterMaking()`, not in `definition()`. This lets callers override the title (e.g. `Post::factory(['title' => 'Custom'])->create()`) and still get a correct slug. Do not move slug generation into `definition()`.

## Draft state = `published_at` null
The `draft()` state on PostFactory, GuideFactory, and ProjectFactory sets `published_at => null`. This is the universal unpublished signal across the app.

## User factory: `workos_id` and `avatar` are NOT NULL
The `users` table requires both. The factory uses `'fake-'.Str::random(10)` for `workos_id` and `''` for `avatar`. DatabaseSeeder mirrors this for the seeded test user.
