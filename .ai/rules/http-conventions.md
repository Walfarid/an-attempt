# HTTP Conventions — RMM Level 2 inside an Inertia Monolith

Applies to: `routes/**`, `app/Http/**`, `bootstrap/app.php`

- There is no separate JSON API surface (`/api/v1`). The Inertia web routes themselves follow Richardson Maturity Model **Level 2** discipline:
  - Resource URIs: `/projects`, `/projects/{project}`, admin mutations under `/dashboard/...`.
  - Correct verbs via method spoofing (POST create, PUT/PATCH update, DELETE destroy).
  - Semantic statuses: 200 for Inertia renders, 303 See Other redirects after mutations, 422 validation errors handled by Inertia's error bag, 404 from binding failures.
- Controllers are resource-shaped and thin (<10 lines per method); business logic stays in model scopes or dedicated classes only when complexity demands it.
- Type-hint Form Requests for validation + authorization; use implicit (optionally slug-bound) route model binding.
- Mutations authenticate via the WorkOS session (installed `laravel/workos`) plus policies; public reads show only `published_at IS NOT NULL` content.
- A pure JSON API may be layered on later without redesign because controllers stay resource-shaped; don't pre-build it.
- After adding/changing routes, regenerate frontend bindings with `php artisan wayfinder:generate --with-form --no-interaction` — `--with-form` is required or `.form()` variants disappear and vue-tsc breaks. Generated modules live in `resources/js/{routes,actions}`; import controller route modules via their default export (`import skillsRoute from '@/routes/dashboard/skills'`, then `skillsRoute.index.url()`), not named exports from the aggregate index.
