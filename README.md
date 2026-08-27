# Walfa — Portfolio

> Full-stack portfolio: public site (home + blog) with an authenticated content dashboard,
> contact form, and first-party analytics. Laravel 13 + Inertia v3 + Vue 3.

[![Laravel](https://img.shields.io/badge/Laravel-13-F5325C?logo=laravel&logoColor=white)](https://laravel.com/docs/13.x)
[![Vue](https://img.shields.io/badge/Vue-3-42b883?logo=vuedotjs&logoColor=white)](https://vuejs.org/)
[![Inertia](https://img.shields.io/badge/Inertia-3-5257c3?logo=inertia&logoColor=white)](https://inertiajs.com/)
[![TypeScript](https://img.shields.io/badge/TypeScript-5-3178c6?logo=typescript&logoColor=white)](https://www.typescriptlang.org/)
[![Tailwind](https://img.shields.io/badge/Tailwind-4-06b6d4?logo=tailwindcss&logoColor=white)](https://tailwindcss.com/)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

---

## Table of Contents

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

- Home page with hero, animated stats, and below-the-fold sections
  (skills, experience, projects, education, publications, latest posts)
- Blog index + post pages (`/posts`, `/posts/{slug}`)
- Contact form with Cloudflare Turnstile protection (skipped when no secret is configured)
- `sitemap.xml` and security headers (CSP, HSTS, etc.) applied globally
- First-party analytics: page-view tracking middleware + outbound-click endpoint,
  plus Microsoft Clarity and Google Analytics 4 via env keys

**Dashboard** (auth via WorkOS)

- CRUD management for projects (+ screenshots), posts (+ cover image), skills,
  experiences, educations, publications, and the profile bio
- Confirm-before-delete dialogs with optimistic rollback (`ui/alert-dialog`)
- Analytics overview page

---

## Stack

| Layer | Choice |
|-------|--------|
| Framework | **Laravel 13** (PHP 8.5) |
| Frontend | **Vue 3** + **Inertia v3** (SPA, no SSR) |
| Styling | **Tailwind CSS 4**, custom design tokens in `resources/css/app.css` |
| Language | **TypeScript** (Vue components) + PHP 8.5 |
| Tooling | **Vite 8**, ESLint + Prettier, Laravel Pint, PHPStan/Larastan, Pest |
| Auth | **WorkOS** (`laravel/workos`) |
| Database | **SQLite** for local default; **MariaDB 12.3** (LTS) via Docker for parity |
| Cache/Queue | Database or **Valkey 9.1** (Redis-compatible) via Docker |
| Object storage | **Garage** (S3-compatible, Docker dev) on the `media` disk → Oracle Cloud S3-compatible endpoint in production |
| Local mail | **Mailpit** via Docker |
| Production runtime | **FrankenPHP + Laravel Octane** worker mode (`Dockerfile`) |

Image tags are verified against LTS/stable lines at the time of writing
(see the "verified" dates in `compose.yaml` and the CI workflow headers).

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

- **PHP 8.5+** (CLI + extensions used by the app, incl. `pdo_mysql`/`pdo_sqlite`)
- **Composer** 2.x
- **Node.js** 24 (matches CI and the production image)
- **Docker** + **Docker Compose v2** (backing services only)
- **Task** ([go-task/task](https://taskfile.dev)) — the project's task runner

### Installation

```bash
task setup          # composer install, .env from .env.example, key, migrations, npm install + build
task setup DB=mariadb   # same, and point .env at the MariaDB/Valkey/Mailpit compose stack
task docker:up      # start backing services (MariaDB, Valkey, Garage, Mailpit)
```

---

## Development

```bash
task up             # Docker services + Laravel server + queue worker + Vite (HMR)
task dev            # just the dev servers: Laravel + queue worker + Vite
```

- Backend server: **http://localhost:8000**
- Mailpit web UI: **http://localhost:8025**
- Garage S3 API: **http://127.0.0.1:3900**

Common shortcuts: `task artisan:run -- make:model Foo`, `task migrate`, `task seed`,
`task routes`, `task shell`, `task artisan:key_check`.

Frontend route bindings are generated by **Wayfinder** (`php artisan wayfinder:generate --with-form`)
and imported in TS as `@/actions/...` / `@/routes/...`; they are gitignored and
regenerated automatically by `npm run dev` / `npm run build` (Vite plugin).

---

## Testing

```bash
task test                   # Pest suite — needs the Docker services (MariaDB + Garage)
php artisan test --compact --filter=PostManagementTest
```

The suite runs against a dedicated `walfa_testing` MariaDB database and exercises the
S3-compatible `media` disk against the local Garage instance, mirroring CI exactly.

---

## Quality checks

```bash
task ci             # everything CI runs locally: lint, types, build, full backend checks
task lint:check     # Pint + ESLint + Prettier (no changes)
task types:check    # PHPStan + vue-tsc
```

---

## Docker backing services

`compose.yaml` provides **infrastructure only** — the app runs natively on the host.

| Service | Image | Purpose |
|---------|-------|---------|
| MariaDB | `mariadb:12.3` | LTS database for production parity |
| Valkey | `valkey/valkey:9.1` | Redis-compatible cache/queue/session |
| Garage | `dxflrs/garage:5b6d138035db7c8b036136921c478f217a61f4e3` | S3-compatible object storage (`media` disk) |
| Mailpit | `axllent/mailpit:v1.31.0` | SMTP testing + web UI |

Garage ports: S3 API **3900**, Admin API **3903**. Config lives in `docker/garage/garage.toml`;
the default bucket and access key are auto-provisioned from `.env` (`GARAGE_*`).

All ports bind to **127.0.0.1** only — nothing is exposed to the network.

```bash
task docker:up      # start (waits for healthy)
task docker:ps      # status + health
task docker:down    # stop, keep data volumes
task docker:reset   # stop + delete volumes (destructive)
```

---

## CI/CD

All pipelines live in `.github/workflows/` and run on push to `main` (and PRs where noted).

| Workflow | What it does |
|----------|--------------|
| `ci.yml` | Multi-stage gate: Pint + PHPStan, ESLint + Prettier + vue-tsc + Vite build, Pest suite (MariaDB + Garage services), `composer audit`, aggregate `ci-ok` job |
| `tests.yml` | `composer setup` + `composer ci:check` (lint, types, PHPStan, full Pest suite) with MariaDB + Garage |
| `security.yml` | gitleaks secret scan, zizmor workflow audit, CodeQL (JS/TS), Semgrep (PHP), dependency review (PRs), OSV-Scanner (PR diff + full) |
| `dast.yml` | Nightly OWASP ZAP baseline scan against a seeded app instance |
| `deploy.yml` | Build multi-arch image (amd64 + arm64) with buildx, push to OCIR, roll out on the app VM via docker compose + `octane:reload` |

Action versions are pinned to immutable commit SHAs with matching version comments
(zizmor enforces this in CI). Dev-only Garage credentials are keep out of the workflows —
they are sourced from `.env` at runtime by the "Start Garage" step.

---

## Deployment

Production runs as a container on an Oracle Cloud VM:

- **Image**: multi-stage `Dockerfile` → FrankenPHP (PHP 8.5) + Laravel Octane worker mode
- **Registry**: OCIR (secrets: `OCIR_*`, `DEPLOY_*` in the repo settings)
- **Rollout**: `docker compose pull && up -d`, `migrate --force`, caches, then `octane:reload`
- **Storage**: S3-compatible Oracle Cloud bucket (swap the `AWS_*`/`GARAGE_*` env block)

Secrets are never committed; dev placeholders in `.env.example` are allowlisted in `.gitleaks.toml`.

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

MIT (`composer.json`) — base from the [Laravel Vue starter kit](https://laravel.com/docs/13.x/starter-kits).