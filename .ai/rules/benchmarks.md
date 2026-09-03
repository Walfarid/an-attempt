---
paths:
  - .github/workflows/ci.yml
  - .github/workflows/deploy.yml
---

# Benchmarks

## Harness: keep dashboard measurements after public routes
benchmarks/baseline.php measures dashboard endpoints via direct controller calls. The Laravel Head `head` prop only renders after at least one kernel request ran in the process, so measuring dashboard endpoints FIRST in a fresh process under-measures them by exactly the head block (1,376 B). Keep the fixed order (public routes first, then dashboard) — it matches production Octane truth. Never reorder without re-verifying bytes.

## Harness measures deferred-props payload and /privacy
baseline.php has three sections in fixed order: public routes (incl. /privacy), deferred props (exact X-Inertia partial headers: INERTIA, PARTIAL_COMPONENT, PARTIAL_ONLY, VERSION from HandleInertiaRequests::version), then dashboard controllers. The deferred section sits between public and dashboard so the head-prop ordering rule (kernel request before dashboard measurement) still holds. Wire trims are verified by exact bytes/bytes_gz diffs (deterministic after masking), timing by median_ms only beyond noise.

## Post-deploy health check on localhost/up
The deploy workflow's SSH script block includes a post-deploy health check after octane:reload and before docker image prune. It sleeps 5 seconds for Octane workers to reload, then curls http://localhost/up with a Host header (matching the compose.prod.yaml Docker healthcheck pattern, --retry 3 --retry-delay 2). Under set -e, a failed health check fails the deploy.

## Post-deploy health check in CI workflow
The deploy workflow includes a post-deploy health check after `octane:reload` and before `docker image prune`. It waits 5 seconds for Octane workers to finish reloading, then uses `curl -sf --retry 3 --retry-delay 2` against the `/up` endpoint on localhost (matching the compose.prod.yaml healthcheck configuration). The `-f` flag makes curl fail on HTTP errors, and `set -e` ensures the entire deploy fails if the health check doesn't pass. This prevents declaring a deploy successful when the app is actually down or misconfigured.
