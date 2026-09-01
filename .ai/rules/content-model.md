# Content Model

Applies to: `database/migrations/**`, `app/Models/**`, `database/factories/**`, `app/Enums/**`

- The frontend types in `resources/js/data/portfolio.ts` (from the CV data) are the source of truth for portfolio entity shapes; backend tables mirror them. Currently: `profiles` (singleton), `skills`, `experiences`, `projects` (+ `project_screenshots` + `project_skill` pivot), `posts`, `education`, `publications`. `DatabaseSeeder` carries the resume content and is idempotent (natural keys + orphan cleanup) — re-running it after a resume update replaces the portfolio data in place.
- `experiences` (NOT timeline_entries): role, company, location, started_at, ended_at (null = present), summary, highlights (JSON array).
- Skill categories are the enum `App\Enums\SkillCategory` (languages/frameworks/databases/devops/platform/security) — keep in sync with the frontend `SkillCategory` type; unique on (name, category), enforced in validation as a clean 422 not a DB error.
- Singleton rows (e.g. profile): one seeded row, fetched via a `current()` helper, edited via GET edit form + PUT update. No create/destroy.
- Publishing model for public content (projects/posts): nullable `published_at` timestamp. NULL = draft; set = public.
- Slugs are the public identifier (`{project:slug}` binding); integer IDs stay internal/admin-only.
- Screenshots/covers store paths on the `media` disk, never absolute URLs. Blog bodies are Markdown via `App\Support\Markdown`.
- Every content table gets a factory + seeder; factories are the default way tests create models.
- Dashboard CRUD uses dialog-style UIs: resource controllers with only index/store/update/destroy (no create/edit pages). One Form Request per entity covers both store and update (e.g. `SkillRequest`); composite-unique rules use `$this->route('skill')` for ignore.
