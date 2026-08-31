---
paths:
  - 'benchmarks/**'
---

# Benchmarks

## Harness: keep dashboard measurements after public routes
benchmarks/baseline.php measures dashboard endpoints via direct controller calls. The Laravel Head `head` prop only renders after at least one kernel request ran in the process, so measuring dashboard endpoints FIRST in a fresh process under-measures them by exactly the head block (1,376 B). Keep the fixed order (public routes first, then dashboard) — it matches production Octane truth. Never reorder without re-verifying bytes.
