---
paths:
  - .github/workflows/deploy.yml
  - .github/workflows/dast.yml
  - .github/workflows/ci.yml
  - Dockerfile
---

# Workflows

## OCI SSH ingress must stay open for GitHub runners
The walfa VM security list (ap-singapore-1, "walfa-app-sl") allows SSH 22 from 0.0.0.0/0 because GitHub's `actions` IP ranges (api.github.com/meta, 250+ CIDRs) exceed the security-list rule budget. Do NOT re-lock port 22 to a single IP — deploys die with `dial tcp ...:22: i/o timeout` (runners are silently dropped). sshd is key-only (PasswordAuthentication no). The VM public IP is ephemeral — if it changes after a reboot, update the DEPLOY_HOST secret.

## DAST workflow triggers
DAST runs on `pull_request` (every PR), nightly schedule (03:30 UTC), and manual trigger (`workflow_dispatch`). It does NOT run on push to main — that would duplicate ci.yml work. The scan boots MariaDB + builds assets + runs migrations (5-10 min). PRs trigger DAST before merge; nightly catches regressions on main; manual trigger available for ad-hoc scans. Do NOT re-add a push trigger without a compelling security reason.

## Container images must be pinned to digests
ALL container images in `.github/workflows/**` and `Dockerfile` must be pinned to their SHA-256 digest (e.g. `mariadb@sha256:abcdef...`) with a `# verified YYYY-MM-DD (ImageName X.Y.Z)` comment recording the resolution date and version. The `compose.yaml` dev file uses patch-version pins instead (e.g. `mariadb:12.3.3`) for readability — digests are not required there, but the version must still be the latest patch on the series. Dependabot's `docker` ecosystem keeps workflow and Dockerfile digests fresh. When bumping a pinned digest, re-resolve via `docker pull` + `docker inspect --format='{{index .RepoDigests 0}}'` and update the `# verified` date.

## Frontend gates in ci.yml: vitest and npm audit are mandatory
The `frontend` job of `ci.yml` MUST keep the `npm run test` (vitest) and `npm audit --audit=high` steps between vue-tsc and the Vite build. Frontend unit tests live in `resources/js/**/*.test.ts` and are never run anywhere else; removing either step silently drops coverage or JS dependency advisories. These gates are mirrored locally as `task npm:test` / `task npm:audit` inside `task ci` — keep the mirror (see .ai/rules/taskfile.md "CI parity") or local runs stop predicting CI. The workflow list in AGENTS.md/README says FOUR workflows — there is no `tests.yml`; do not re-add references to it.

## Backend static + SCA gates are mirrored in task ci too
`ci.yml`'s Pint (`pint --test --parallel`), PHPStan (`analyse --no-progress`), and `composer audit --locked` steps have local counterparts (`task composer:lint_check`, the shared PHPStan flags, `task composer:audit`) wired into `task ci`. Do not change the invocation flags in one place without the other.
