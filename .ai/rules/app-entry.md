---
paths:
  - resources/js/app.ts
---

# App Entry Point

## Three Vue app instances, one DOM
`app.ts` mounts three independent Vue apps: (1) the Inertia app inside the server-rendered root, (2) `PageDrawLoader` on a `#page-loader-root` div appended to `document.body`, (3) `CookieConsentBanner` on a `#consent-banner-root` div appended to `document.body`. The loader and banner live outside the Inertia root so they survive SPA navigations and are not affected by the route-transition fade.

## Boot order is load-bearing
Inside `setup()`: mount app → `initRouteTransition(el)` → `dispatchEvent('page-loader:boot-complete')`. The custom event tells the loader to transition from its boot phase to the revealed state. `initializeTheme()` and `initializeFlashToast()` run after all three apps are mounted — theme must be set before any layout paints; flash-toast only attaches a `router.on('flash', …)` listener so it has no ordering dependency beyond `router` existing.

## Dashboard layouts stay async — never import them synchronously
`AppLayout` and `SettingsLayout` are `defineAsyncComponent(...)`. Their reka-ui / sonner dependencies are only fetched when a dashboard or settings page is visited. Importing them synchronously drags the entire dashboard shell onto every public page's bundle.

## Layout resolver matches by component name, not by path
The `layout` callback receives the page component name (`'Welcome'`, `'settings/Appearance'`, `'posts/Show'`, etc.). Public pages (`Welcome`, `Privacy`, `posts/*`, `guides/*`) return `null` — they use `PublicLayout` imported directly in their template. Settings pages return `[AppLayout, SettingsLayout]` (Inertia nests them left-to-right). Everything else returns `AppLayout`. Adding a new page means checking which branch it should fall into — an unrecognised name silently gets `AppLayout`.

## `serverHead: true` is on
Inertia v3 server-side `<head>` management is enabled. Do not disable it — it is required for correct SSR head output (SEO meta, OG tags).

## Progress bar is self-drawing, not Inertia's default spinner
`progress: { color: '#17594a', showSpinner: false }` disables the built-in spinner. The `PageDrawLoader` component listens to Inertia progress events and renders its own top-bar animation instead.

## `el` null guard in setup
The `if (!el) return` guard protects against Inertia calling setup without a target element. Any code that depends on the mounted app (route transitions, boot-complete event) must be inside this guard — code placed after `createInertiaApp` but outside `setup` is unaffected.
