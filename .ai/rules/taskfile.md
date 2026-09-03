---
paths:
  - Taskfile.yml
  - tools/composer.Taskfile.yml
  - tools/npm.Taskfile.yml
  - tools/artisan.Taskfile.yml
---

# Taskfile

## File layout: root + three namespaced includes
The root `Taskfile.yml` defines daily-driver tasks (`setup`, `up`, `dev`, `test`, `ci`, `lint`, `types:check`, `docker:*`). Domain-specific tasks live in `tools/<tool>.Taskfile.yml` and are included with namespace aliases: `composer:`, `npm:`, `artisan:`. Each included file exposes a `run:` passthrough task that forwards `{{.CLI_ARGS}}` to the underlying tool — use these for one-off commands (`task artisan:run -- make:model Foo`).

## Naming: kebab-case at root, snake_case inside included files
Top-level composite tasks use colon-separated kebab-case (`lint:check`, `types:check`, `docker:up`). Inside the included `tools/*.Taskfile.yml` files, multi-word task names use snake_case (`migrate_fresh`, `key_check`, `lint_check`, `types_check`, `build_ssr`). Follow whichever convention the enclosing file already uses — do not mix styles within one file.

## Always prefix vendor/bin invocations with `php`
Run `php vendor/bin/pint`, `php vendor/bin/phpstan`, `php vendor/bin/rector` — never bare `vendor/bin/pint`. The `php` prefix works identically on Linux, macOS, and Windows, keeping tasks portable across dev machines.

## CI parity: `task ci` must mirror `.github/workflows/ci.yml` gate-for-gate
`task ci` exists so that green locally means green on GitHub. Its `cmds:` list must exercise every gate `ci.yml` enforces, in the same check order as far as services allow: Wayfinder binding regeneration (gitignored `@/routes` + `@/actions`, required before any frontend check), lint (Pint + ESLint + Prettier), types (PHPStan + vue-tsc), `npm:test` (vitest), `npm:audit` (`npm audit --audit=high`), `composer:audit` (`composer audit --locked`), `npm:build` (Vite), then `test` (Pest against MariaDB + Garage).
Root `test` runs the full parallel-capable suite the same way CI's "Run test suite" step does (`php artisan test --parallel`; serial locally only when debugging). `test` never builds assets or regenerates Wayfinder bindings — CI builds those in prior steps and bindings/manifest may already exist locally, so keep `test` runnable with just `docker:up`.
When adding a gate to `ci.yml`, add the matching root/namespace task in the same change — never land a CI-only gate. To verify parity, diff the step lists: every `run:` check in `ci.yml`'s `backend-static`, `frontend`, `tests`, and `sca-composer` jobs must have a counterpart in `task ci --dry` output. Known deliberate differences (document here when adding one): CI regenerates Wayfinder bindings + builds assets before testing (fresh runner); local `test` relies on `docker:up` + existing bindings/manifest. PHPStan runs `analyse --memory-limit=512M --no-progress` in both places — the flags were unified so a locally green run cannot fail CI on memory or output flags.

## ci task uses sequential cmds, not deps
The `ci` task chains steps as `cmds:` (sequential) instead of `deps:` because deps would run checks concurrently. The checks must not start before `composer:setup` finishes populating `vendor/` and `node_modules/`. When adding a new CI step, append it to the `cmds:` list in order.

## setup deliberately omits sources/generates
`setup` is meant to be safely re-runnable at any time. Adding Task's checksum-based `sources:`/`generates:` would skip the whole chain whenever lockfiles are unchanged — that is wrong for setup, which must always execute every step (key generation is guarded by its own `status:` check instead).

## DB=mariadb patches .env for service containers
Running `task env:prepare DB=mariadb` appends MariaDB + Valkey connection vars to `.env`. The default (no `DB` var) keeps SQLite for zero-config local dev. The grep guard (`test -f .env`, `grep -q '^DB_HOST=127.0.0.1'`) makes the task idempotent — it only writes when the block is not already present.

## test task requires Docker services
The root `test` task declares `deps: [docker:up, artisan:config_clear]` because tests hit MariaDB (not SQLite). Calling `php artisan test` directly without Docker services up will fail.
