---
paths:
  - resources/js/composables/**
---

# Composables

## GSAP composables share a disposal pattern
`useHeroScene`, `useScrollAnimations`, and `useCountUp` lazy-import gsap on `onMounted`. Because the import is async, the component may already be unmounted when the promise resolves. Every one of them sets a `disposed = true` flag in `onUnmounted` and checks it after the `await` — any new GSAP-based composable must follow the same shape. All three call `mm?.revert()` on unmount to tear down `MatchMedia` contexts and their listeners.

## Scroll-animation refresh is the deferred-props contract
Pages whose deferred Inertia props render `[data-motion]` or `[data-motion-group]` elements must call `refresh()` when those props arrive (typically via a `watch` on the deferred data). The `seen` Set inside the composable prevents re-animating already-tracked elements, and `ScrollTrigger.refresh()` recalculates trigger positions after layout growth. Calling `refresh()` before gsap has loaded queues a rescan via `pendingRefresh`.

## Module-level refs are singletons across components
`useConsent` (`bannerVisible`, `consent`), `useAppearance` (`appearance`), and `useCurrentUrl` (`currentUrlReactive`) declare their reactive state at module scope so the banner, privacy page, nav, and header share one state. Do not move these inside the function body — doing so would create per-component state and break the shared contract.

## `init*` / `initialize*` are boot-level, not composables
`initRouteTransition` and `initializeTheme` are called from `app.ts`, not from `setup()`. They have no return value and attach listeners for the app lifetime. `initRouteTransition` has an HMR guard (`listenersAttached`) to prevent duplicate listeners across hot reloads.

## Route transition must clear the inline transform
`initRouteTransition` removes `transform` from the root element after the reveal animation completes. GSAP leaves `transform: translateY(0px)` as an inline style, which creates a new containing block and breaks `position: fixed` for descendants (the dashboard sidebar scrolls with the page). The `onComplete` callback and the landing-page early return both clear it.

## Route transition ignores non-navigation Inertia events
Prefetch visits, deferred-prop syncs, and partial reloads (`visit.only`) must not trigger the fade — they are background data fetches and hiding the root would blank the page. The landing page (`path === '/'`) is also exempt because it plays its own hero choreography.

## Inertia v3 passes `visit.url` as a URL object
`initRouteTransition` handles both `string` and `URL` for `visit.url`. Do not assume it is a string.

## `usePageLoader` boot cap
A 3-second `setTimeout` hard-caps the boot phase in case `page-loader:boot-complete` never fires. The timer is cleared on both `completeBoot` and `onBeforeUnmount`.

## `useImageProcessor` is a pure utility, not a reactive composable
`processImage` is a standalone async function (no refs, no lifecycle). It randomizes filenames via `crypto.randomUUID()` so original names never reach the server. Images within max dimensions and file size are passed through with only the filename changed — no unnecessary re-encoding. WebP support detection is cached module-level; `resetWebpSupportCache()` exists for tests only.

## `useConsent` reloads the page on accept/decline
Both `acceptAll` and `declineAll` call `window.location.reload()` after persisting the choice. This is deliberate: the server reads the `consent` cookie to decide which third-party scripts to include, so a client-side toggle alone is not enough.

## `useAppearance` dual-persists to localStorage and cookie
`localStorage` drives client-side reactivity; the `appearance` cookie drives SSR so the server can emit the correct `dark` class on initial HTML. Both must stay in sync. The system-theme `matchMedia` listener has an HMR guard (`systemThemeListenerAttached`).

## `useCurrentUrl` parses at module scope
`usePage()` and the `computed` URL are resolved once at import time, not per call. The composable returns a `readonly` wrapper so consumers cannot mutate the reactive source.
