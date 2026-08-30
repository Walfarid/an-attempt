# Walfa

Personal portfolio: public site (home + blog), authenticated content dashboard,
contact form, first-party analytics. Laravel 13, Inertia v3, Vue 3.

[![Laravel](https://img.shields.io/badge/Laravel-13-F5325C?logo=laravel&logoColor=white)](https://laravel.com/docs/13.x)
[![Vue](https://img.shields.io/badge/Vue-3-42b883?logo=vuedotjs&logoColor=white)](https://vuejs.org/)
[![Inertia](https://img.shields.io/badge/Inertia-3-5257c3?logo=inertia&logoColor=white)](https://inertiajs.com/)
[![TypeScript](https://img.shields.io/badge/TypeScript-5-3178c6?logo=typescript&logoColor=white)](https://www.typescriptlang.org/)
[![Tailwind](https://img.shields.io/badge/Tailwind-4-06b6d4?logo=tailwindcss&logoColor=white)](https://tailwindcss.com/)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

---

## Contents

- [What's here](#whats-here)
- [Stack](#stack)
- [Repository layout](#repository-layout)
- [Getting started](#getting-started)
- [Development](#development)
- [Testing](#testing)
- [Quality checks](#quality-checks)
- [Docker backing services](#docker-backing-services)
- [CI/CD](#cicd)
- [Deployment](#deployment)
- [Linting / formatting](#linting--formatting)
- [License](#license)

---

## What's here

**Public site**

- Home page: hero, animated stats, skills, experience, projects, education, publications, latest posts
- Blog index and post pages at `/posts` and `/posts/{slug}`
- Contact form behind Cloudflare Turnstile (disabled when no secret is configured)
- `sitemap.xml` and security headers (CSP, HSTS) applied globally
- Page-view tracking middleware and outbound-click endpoint, plus Microsoft Clarity and GA4 via env keys

**Dashboard** (WorkOS auth)

- CRUD for projects (+ screenshots), posts (+ cover image), skills, experiences, educations, publications, profile bio
- Confirm-before-delete dialogs with optimistic rollback via `ui/alert-dialog`
- Analytics overview page

---

## Stack

| Layer | Choice |
|-------|--------|
| Framework | Laravel 13 (PHP 8.5) |
| Frontend | Vue 3 + Inertia v3 (SPA, no SSR) |
| Styling | Tailwind CSS 4, custom design tokens in `resources/css/app.css` |
| Language | TypeScript (Vue components) + PHP 8.5 |
| Tooling | Vite 8, ESLint + Prettier, Laravel Pint, PHPStan/Larastan, Pest |
| Auth | WorkOS (`laravel/workos`) |
| Database | SQLite locally; MariaDB 12.3 (LTS) via Docker for parity |
| Cache/Queue | Database or Valkey 9.1 (Redis-compatible) via Docker |
| Object storage | Garage (S3-compatible, Docker dev) on the `media` disk; Oracle Cloud S3 endpoint in production |
| Local mail | Mailpit via Docker |
| Production runtime | FrankenPHP + Laravel Octane worker mode (`Dockerfile`) |

`compose.yaml` and the CI workflow headers carry verified dates for each image tag.

---

## Repository layout

```
app/
  Http/Controllers/          public site + Dashboard/* and Settings/* controllers
  Mail/                      contact-form notification
  Models/                    Profile, Project (+Screenshot), Post, Skill, Experience,
                             Education, Publication, ContactMessage, PageView, Click, User
  Middleware/                SecurityHeaders, TrackPageView, HandleInertiaRequests
routes/web.php               all public + authenticated routes
resources/js/pages/          Inertia pages: Welcome, posts/, dashboard/
resources/js/components/     shared chrome + ui/ (alert-dialog) + site/ (hero, header, skeleton)
resources/js/composables/    useHeroScene, useCountUp, useScrollAnimations, useRouteTransition, ...
docker/                      garage.toml (S3 config) and mariadb init scripts
.github/workflows/           CI, tests, Security, DAST, Deploy pipelines
.ai/rules/                   area-grouped conventions read by AI tooling (see index.md)
```

---

## Getting started

### Prerequisites

- PHP 8.5+ (with `pdo_mysql`/`pdo_sqlite`)
- Composer 2.x
- Node.js 24 (matches CI and the production image)
- Docker + Docker Compose v2 (backing services only)
- Task ([go-task/task](https://taskfile.dev))

### Installation

```bash
task setup              # composer install, .env from .env.example, key, migrations, npm install + build
task setup DB=mariadb   # same, and configure .env for the MariaDB/Valkey/Mailpit compose stack
```

Both variants start Docker services as part of setup. To start them again later:

```bash
task docker:up          # start backing services (MariaDB, Valkey, Garage, Mailpit)
```

---

## Development

```bash
task up     # Docker services + Laravel server + queue worker + Vite (HMR)
task dev    # just the dev servers: Laravel + queue worker + Vite
```

- Backend: **http://localhost:8000**
- Mailpit: **http://localhost:8025**
- Garage S3 API: **http://127.0.0.1:3900**

Shortcuts: `task artisan:run -- make:model Foo`, `task migrate`, `task seed`,
`task routes`, `task shell`, `task artisan:key_check`.

[Wayfinder](https://github.com/laravel/wayfinder) generates TypeScript route bindings
(`php artisan wayfinder:generate --with-form`), imported as `@/actions/...` / `@/routes/...`.
These files are gitignored and regenerated by `npm run dev` / `npm run build` via the Vite plugin.

---

## Testing

```bash
task test       # Pest suite — requires Docker services (MariaDB + Garage)
php artisan test --compact --filter=PostManagementTest
```

Tests run against a dedicated `walfa_testing` MariaDB database and hit the local Garage
instance on the `media` disk, matching CI.

---

## Quality checks

```bash
task ci             # everything CI runs: lint, types, build, full backend checks
task lint:check     # Pint + ESLint + Prettier (read-only)
task types:check    # PHPStan + vue-tsc
```

---

## Docker backing services

`compose.yaml` runs infrastructure only. The app itself runs natively on the host.

| Service | Image | Purpose |
|---------|-------|---------|
| MariaDB | `mariadb:12.3` | LTS database |
| Valkey | `valkey/valkey:9.1` | Redis-compatible cache/queue/session |
| Garage | `dxflrs/garage:5b6d138035db7c8b036136921c478f217a61f4e3` | S3-compatible object storage (`media` disk) |
| Mailpit | `axllent/mailpit:v1.31.0` | SMTP testing + web UI |

Garage ports: S3 API **3900**, Admin API **3903**. Config in `docker/garage/garage.toml`;
the default bucket and access keys are provisioned from `.env` (`GARAGE_*`).

All ports bind to **127.0.0.1** only.

```bash
task docker:up      # start (waits for healthy)
task docker:ps      # status + health
task docker:down    # stop, keep data volumes
task docker:reset   # stop + delete volumes (destructive)
```

---

## CI/CD

Five workflows in `.github/workflows/`, triggered on push to `main` (and PRs where noted).

| Workflow | Runs |
|----------|------|
| `ci.yml` | Pint + PHPStan, ESLint + Prettier + vue-tsc + Vite build, Pest (MariaDB + Garage), `composer audit`, aggregate `ci-ok` job |
| `tests.yml` | `composer setup` + `composer ci:check` (lint, types, PHPStan, full Pest suite) with MariaDB + Garage |
| `security.yml` | gitleaks, zizmor, CodeQL (JS/TS), Semgrep (PHP), dependency review (PRs), OSV-Scanner (PR diff + full) |
| `dast.yml` | Nightly OWASP ZAP baseline scan against a seeded app instance |
| `deploy.yml` | Multi-arch buildx (amd64 + arm64) → OCIR, roll out on the app VM via docker compose + `octane:reload` |

Action versions are pinned to immutable commit SHAs with matching version comments; zizmor enforces this in CI. Garage credentials stay out of the workflows and come from `.env` at runtime.

---

## Deployment

Production runs as a container on an Oracle Cloud VM.

- **Image**: multi-stage `Dockerfile` producing FrankenPHP (PHP 8.5) + Octane worker mode
- **Registry**: OCIR (secrets: `OCIR_*`, `DEPLOY_*` in repo settings)
- **Rollout**: `docker compose pull && up -d`, `migrate --force`, cache warm, `octane:reload`
- **Storage**: Oracle Cloud S3-compatible bucket (swap the `AWS_*`/`GARAGE_*` env block)

No secrets in the repo. Dev placeholders in `.env.example` are allowlisted in `.gitleaks.toml`.

---

## Linting / formatting

| Area | Tool | Command |
|------|------|---------|
| PHP | Laravel Pint | `task composer:lint` / `composer:lint_check` |
| PHP static analysis | PHPStan | `task composer:types_check` |
| JS/TS lint | ESLint | `task npm:lint` / `npm:lint_check` |
| Formatting | Prettier | `task npm:format_check` |
| Frontend types | vue-tsc | `task npm:types_check` |
| PHP upgrades | Rector | `task rector` |

---

## License

MIT (`composer.json`). Based on the [Laravel Vue starter kit](https://laravel.com/docs/13.x/starter-kits).