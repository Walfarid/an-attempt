---
paths:
  - resources/js/layouts/**
---

# Layouts

## Public pages wrap their layout, dashboard pages do not
Public pages (`Welcome`, `Privacy`, `posts/*`, `guides/*`) import `PublicLayout` directly in the template and opt out of the Inertia `layout` resolver by returning `null` in `app.ts`. Dashboard and settings pages never import a layout component — they rely on the resolver and pass breadcrumbs via `defineOptions({ layout: { breadcrumbs: [...] } })`.

## `AppLayout.vue` is a pass-through
The top-level `AppLayout.vue` only forwards `breadcrumbs` to `app/AppSidebarLayout.vue` and renders the default slot. Do not add markup or logic here — change `AppSidebarLayout.vue` instead.

## Settings pages get a two-layout stack
`app.ts` returns `[AppLayout, SettingsLayout]` for `settings/*` pages. Inertia nests them left-to-right: `AppLayout` (sidebar shell + `<Toaster />`) wraps `settings/Layout.vue` (the profile/appearance nav and content pane). Adding a new settings page means it inherits the sidebar nav defined inside `settings/Layout.vue`; update the `sidebarNavItems` array there.

## Dashboard layouts are async-loaded to keep the public bundle lean
`AppLayout` and `SettingsLayout` are `defineAsyncComponent(...)` in `app.ts`. Their reka-ui/sonner dependencies are only fetched when a dashboard or settings page is visited. Do not import them synchronously.

## `PublicLayout` props
`mainClass` overrides the `<main>` wrapper classes (default: `mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-24`). `Welcome.vue` passes `mainClass=""` because its hero fills the viewport. `profile` is forwarded to `SiteFooter` — omit it on internal pages (Privacy, posts, guides) where the footer should render without social links.

## Breadcrumbs are page-level, not route-level
Each dashboard/settings page declares its own `breadcrumbs` array in `defineOptions({ layout: ... })`. They flow through `AppSidebarLayout` → `AppSidebarHeader`. When adding a new dashboard page, you must declare them — the layout does not auto-generate them from the route.

## Sidebar collapse state persists via `sidebar_state` cookie, not localStorage or a prop
The sidebar open/closed toggle is written to a `sidebar_state` cookie (set client-side in `components/ui/sidebar/utils.ts`). The server reads it in `HandleInertiaRequests` to set the initial `sidebarOpen` shared prop, and the cookie is excluded from encryption in `bootstrap/app.php` so JavaScript can read/write it directly. Do not refactor this to localStorage, a database column, or a plain Inertia prop — the cookie is the single source of truth and is pinned by `HandleInertiaRequestsTest`.
