---
paths:
  - 'tests/**'
---

# Tests

## Run Pest directly when the CLI memory limit bites
`php artisan test` re-execs the PHP binary without inheriting `-d` flags, so `-d memory_limit=...` is silently dropped and the suite dies at the 128M default (Faker markov indexing). Run `php -d memory_limit=1G vendor/bin/pest --compact` instead.

## Rules index integrity is enforced by tests/Unit/Support/RulesIndexTest.php
`.ai/rules/index.md` must keep every rule file indexed and every listed glob resolvable. The unit test (tests/Unit/Support/RulesIndexTest.php) enforces: (1) every `.ai/rules/*.md` except `index.md` appears in the index; (2) every `paths:` frontmatter entry (YAML list, repo-root-relative, `**` recursive) resolves to a real file/dir; (3) every backtick glob in the index's Applies-to column resolves; (4) no duplicate rule entries. When adding a new rule file, add an index row and a resolvable `paths:` entry in the same commit — otherwise CI fails.
