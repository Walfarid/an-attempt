---
paths:
  - resources/js/lib/utils.ts
---

# Lib Utils

## Always use `cn()` for class composition

Every component must compose Tailwind classes through `cn()` (re-exported from `@/lib/utils`), never raw string concatenation or bare `clsx`/`twMerge` calls. `cn` wraps `clsx` + `tailwind-merge` so conflicting utility classes resolve correctly (e.g. `cn('px-2', 'px-4')` → `'px-4'`).

## `toUrl()` normalizes Inertia link hrefs

Inertia's `Link` `href` prop accepts `string | URL | { url: string, ... }`. Use `toUrl(href)` from `@/lib/utils` whenever you need the plain string form (e.g. for `window.location`, comparison, or passing to a non-Inertia component). Do not reach for `href.url` directly — the prop may already be a string.

## `formatDate()` uses `en-US` locale

The helper hardcodes `en-US` with `{ month: 'short', day: 'numeric', year: 'numeric' }`. If the site ever needs i18n, this is the single place to change; until then, do not add locale parameters at call sites.
