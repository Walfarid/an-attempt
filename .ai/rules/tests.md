---
paths:
  - 'tests/**'
---

# Tests

## Run Pest directly when the CLI memory limit bites
`php artisan test` re-execs the PHP binary without inheriting `-d` flags, so `-d memory_limit=...` is silently dropped and the suite dies at the 128M default (Faker markov indexing). Run `php -d memory_limit=1G vendor/bin/pest --compact` instead.

## Every middleware and dashboard Form Request must be covered
Each middleware registered in `bootstrap/app.php` needs a matching `tests/Feature/Http/Middleware/<Name>Test.php`; each Form Request in `app/Http/Requests/Dashboard/` needs a `tests/Unit/Http/Requests/<Name>Test.php`. Consent-gated shared props (HandleInertiaRequests adsense keys) must be tested for both accepted and non-accepted paths. Cover upload/remove endpoints (post + guide) must have upload, delete, and cascade-cleanup tests.

## Unit request tests that hit the DB need RefreshDatabase
Form-request tests that POST/PUT via HTTP (exercising prepareForValidation + Rule::unique()->ignore()) create rows that persist across the suite and pollute later content tests (e.g. GuideRequestTest polluting GuideRenderingTest). Use `uses(TestCase::class, RefreshDatabase::class)` in any request test that writes. Plain `rules()`-only unit tests don't need it. Also: when asserting Inertia props via `assertInertia`, request the page WITHOUT Inertia headers (plain get) — an XHR-style request (X-Inertia header) returns a JSON array original that `assertInertia` rejects.

## Rules index integrity is enforced by tests/Unit/Support/RulesIndexTest.php
`.ai/rules/index.md` must keep every rule file indexed and every listed glob resolvable. The unit test (tests/Unit/Support/RulesIndexTest.php) enforces: (1) every `.ai/rules/*.md` except `index.md` appears in the index; (2) every `paths:` frontmatter entry (YAML list, repo-root-relative, `**` recursive) resolves to a real file/dir; (3) every backtick glob in the index's Applies-to column resolves; (4) no duplicate rule entries. When adding a new rule file, add an index row and a resolvable `paths:` entry in the same commit — otherwise CI fails.

## PHPStan level 8 is the standing floor
`phpstan.neon` pins `level: 8`. Do not lower it. Nullable model columns (`Carbon|null`, `string|null`, `User|null`) are guarded at call sites with `?->` / `??` / early null-check — not suppressed with `@phpstan-ignore-line`.
