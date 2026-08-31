---
paths:
  - 'benchmarks/**'
---

# Benchmarks

## Harness: keep dashboard measurements after public routes
benchmarks/baseline.php measures dashboard endpoints via direct controller calls. The Laravel Head `head` prop only renders after at least one kernel request ran in the process, so measuring dashboard endpoints FIRST in a fresh process under-measures them by exactly the head block (1,376 B). Keep the fixed order (public routes first, then dashboard) — it matches production Octane truth. Never reorder without re-verifying bytes.

## Harness measures deferred-props payload and /privacy
baseline.php has three sections in fixed order: public routes (incl. /privacy), deferred props (exact X-Inertia partial headers: INERTIA, PARTIAL_COMPONENT, PARTIAL_ONLY, VERSION from HandleInertiaRequests::version), then dashboard controllers. The deferred section sits between public and dashboard so the head-prop ordering rule (kernel request before dashboard measurement) still holds. Wire trims are verified by exact bytes/bytes_gz diffs (deterministic after masking), timing by median_ms only beyond noise.
