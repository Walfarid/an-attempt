---
paths:
  - 'tests/**'
---

# Tests

## Run Pest directly when the CLI memory limit bites
`php artisan test` re-execs the PHP binary without inheriting `-d` flags, so `-d memory_limit=...` is silently dropped and the suite dies at the 128M default (Faker markov indexing). Run `php -d memory_limit=1G vendor/bin/pest --compact` instead.
