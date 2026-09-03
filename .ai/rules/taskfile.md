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

## ci task uses sequential cmds, not deps
The `ci` task chains steps as `cmds:` (sequential) instead of `deps:` because deps would run checks concurrently. The checks must not start before `composer:setup` finishes populating `vendor/` and `node_modules/`. When adding a new CI step, append it to the `cmds:` list in order.

## setup deliberately omits sources/generates
`setup` is meant to be safely re-runnable at any time. Adding Task's checksum-based `sources:`/`generates:` would skip the whole chain whenever lockfiles are unchanged — that is wrong for setup, which must always execute every step (key generation is guarded by its own `status:` check instead).

## DB=mariadb patches .env for service containers
Running `task env:prepare DB=mariadb` appends MariaDB + Valkey connection vars to `.env`. The default (no `DB` var) keeps SQLite for zero-config local dev. The grep guard (`test -f .env`, `grep -q '^DB_HOST=127.0.0.1'`) makes the task idempotent — it only writes when the block is not already present.

## test task requires Docker services
The root `test` task declares `deps: [docker:up, artisan:config_clear]` because tests hit MariaDB (not SQLite). Calling `php artisan test` directly without Docker services up will fail.
