# Portfolio

> **Status:** early-stage portfolio project, built on the Laravel 13 + Inertia v3 + Vue 3 starter kit.

[![Laravel](https://img.shields.io/badge/Laravel-13-F5325C?logo=laravel&logoColor=white)](https://laravel.com/docs/13.x)
[![Vue](https://img.shields.io/badge/Vue-3-42b883?logo=vuedotjs&logoColor=white)](https://vuejs.org/)
[![Inertia](https://img.shields.io/badge/Inertia-3-5257c3?logo=inertia&logoColor=white)](https://inertiajs.com/)
[![TypeScript](https://img.shields.io/badge/TypeScript-5-3178c6?logo=typescript&logoColor=white)](https://www.typescriptlang.org/)
[![Tailwind](https://img.shields.io/badge/Tailwind-4-06b6d4?logo=tailwindcss&logoColor=white)](https://tailwindcss.com/)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

A **[Your Name]** — **Full-stack / Backend / Frontend Developer** (set your focus below) portfolio built on the Laravel + Vue starter kit. Full-stack engineer generalist exploring Laravel, Vue, and beyond.

> **Note:** this project is a work in progress. It currently runs the stock starter kit; the portfolio itself is defined by the roadmap below.

---

## Table of Contents

- [Stack](#stack)
- [Getting started](#getting-started)
  - [Prerequisites](#prerequisites)
  - [Installation](#installation)
- [Development](#development)
- [Testing](#testing)
- [Quality checks](#quality-checks)
- [Docker backing services](#docker-backing-services)
- [Linting / formatting](#linting--formatting)
- [Roadmap](#roadmap)
- [License](#license)

---

## Stack

Built on the official **Laravel Vue starter kit** — no framework reinvention, just a clean baseline to grow from.

| Layer | Choice |
|-------|--------|
| Framework | **Laravel 13** (PHP 8.5) |
| Frontend | **Vue 3** + **Inertia v3** (SPA, no SSR) |
| Styling | **Tailwind CSS 4** |
| Language | **TypeScript** (Vue components) + PHP 8.5 |
| Tooling | **Vite 8**, ESLint + Prettier, Laravel Pint, PHPStan, Pest |
| Auth | **WorkOS** (Laravel WorkOS package) |
| Database | **SQLite** by default; **MariaDB 12.3** (LTS) via Docker for production-like parity |
| Cache/Queue | Database default; **Valkey 9.1** (Redis-compatible) via Docker |
| Local mail | **Mailpit** via Docker |

All image tags are verified LTS/stable/anchor as of 2026-08-23.

---

## Getting started

### Prerequisites

- **PHP 8.5+** (CLI + required extensions incl. `pdo_sqlite`)
- **Composer** 2.x
- **Node.js** 20+ (npm)
- **Docker** + **Docker Compose v2** (only for the backing services — MariaDB, Valkey, Mailpit)
- **Task** ([go-task/task](https://taskfile.dev)) — the project's task runner

> **Windows note:** install [Tailwind](https://tailwindcss.com/docs/installation) and [Laravel](https://laravel.com/docs/13.x/installation) tooling per their docs; the Taskfile is configured so all long-running commands work on Windows `cmd`, Git Bash, and WSL.

### Installation

```bash
# 1. Install dependencies and prepare the environment
task setup
```

`task setup` runs, in order:

1. `composer install`
2. Create `.env` from `.env.example` (if missing)
3. Generate the application key
4. Start the Docker backing services (MariaDB, Valkey, Mailpit)
5. Run database migrations
6. `npm install`
7. `npm run build`

> **Use MariaDB + Valkey instead of SQLite?** The app defaults to SQLite (zero-config). To use the compose stack:
>
> ```bash
> task setup DB=mariadb
> ```
>
> This appends the MariaDB/Valkey connection + cache/queue/session config to `.env`.

---

## Development

```bash
# Start Docker services + both dev servers (Laravel + Vite)
task up

# …or run just the dev servers (composer dev): Laravel server + queue worker + Vite
task dev
```

- Backend server: **http://localhost:8000**
- Vite dev server: HMR-enabled
- Mailpit web UI (if using SMTP): **http://localhost:8025**

### Common artisan shortcuts

```bash
task artisan:run -- make:model Project        # run any artisan command
task artisan:migrate                          # run migrations
task artisan:migrate_fresh                    # drop + re-migrate (destructive)
task artisan:seed                             # seed the database
task artisan:key_check                        # generate a key when APP_KEY is empty
task migrate / task seed / task shell / task routes
```

---

## Testing

```bash
# Run the PHP test suite (Pest)
task test

# Filter by test name
task test -- --filter=DashboardTest
```

---

## Quality checks

```bash
# Full CI pipeline: composer setup, lints, type checks, frontend build, tests
task ci

# Individual checks
task lint:check        # Pint + ESLint + Prettier (no changes)
task types:check       # PHPStan + vue-tsc
```

This mirrors (and extends) the repo's GitHub Actions workflow (`.github/workflows/tests.yml`).

---

## Docker backing services

`compose.yaml` provides **infrastructure only** — the app runs natively on the host.

| Service | Image (verified 2026-08-23) | Purpose |
|---------|------------------------------|---------|
| MariaDB | `mariadb:12.3` | current LTS — primary MySQL-compatible database |
| Valkey | `valkey/valkey:9.1` | current stable — Redis-compatible cache/queue/session |
| Mailpit | `axllent/mailpit:v1.31.0` | pinned — SMTP testing + web UI |

Ports bind to the **loopback** interface only (`127.0.0.1`) — nothing is exposed to the network.

```bash
task docker:up      # start (with --wait: healthy before returning)
task docker:down    # stop (keeps data volumes)
task docker:ps      # status + health
task docker:reset   # stop + delete volumes (destructive)
```

Drivers switch via env: `task setup DB=mariadb` configures `.env` with `DB_CONNECTION=mariadb`, Valkey host/port, and SMTP/Mailpit.

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

## Roadmap

The portfolio is at the **idea → scaffold** stage. Planned sections (each becomes its own page/route in this app):

- **Home / Hero** — name, tagline, one-liner, CTA links
- **Projects** — featured work: screenshots, stack badges, links (live + repo)
- **About** — bio, timeline, tools/skills
- **Contact / Socials** — form (Mailpit for testing), email, GitHub, LinkedIn
- **Blog / Writing** *(optional)* — ramblings, powered by the same stack
- **Resume / CV** — downloadable copy

### Roadmap milestones

- [ ] Replace stock `<Welcome>`/`<Dashboard>` with real portfolio layout (frontend)
- [ ] Seed data: `projects` table + factory/seeder (backend)
- [ ] Wire links in the hero/contact sections
- [ ] Auth (WorkOS) behind the scenes for editing content
- [ ] Deploy: Laravel + Vite build + MariaDB (production weave)

---

## License

This project is licensed under the **MIT License** (`composer.json`). See [`LICENSE.md`](https://opensource.org/licenses/MIT) (Laravel starter kit).