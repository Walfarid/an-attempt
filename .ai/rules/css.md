---
paths:
  - resources/css/**
---

# CSS / Tailwind

## Tailwind 4 — no config file
All Tailwind configuration lives in `resources/css/app.css` via `@theme inline`. There is no `tailwind.config.js`. To add tokens, extend the `@theme` block; do not create a separate config.

## Two variable layers — don't mix them
The file defines **shadcn tokens** (`--background`, `--primary`, …) and **design-system raw tokens** (`--paper`, `--ink`, `--accent`, `--rule`, `--surface`, `--ink-soft`, `--accent-soft`, `--accent-hover`). The shadcn tokens reference the raw ones indirectly (both resolve to the same hex in `:root`/`.dark`). Use the shadcn utility classes (`bg-primary`, `text-foreground`, etc.) in Vue components. Use the raw variables (`var(--paper)`, `var(--ink)`, `var(--rule)`) only inside `app.css` custom utilities and `.site`/`.prose-site` blocks.

## Zero-radius design — all corners are sharp
Every shadcn component override in the `@layer components` block forces `border-radius: 0 !important` (buttons, dialogs, sheets, dropdowns, tooltips, avatars, inputs, sidebar). The `--radius` CSS variable is `0`. The `.d-sharp` utility exists as a belt-and-suspenders escape hatch. Do not add `rounded-*` Tailwind classes to shadcn primitives — they will be overridden.

## Dark mode is class-based, not media-query
`@custom-variant dark (&:is(.dark *))` means the `.dark` selector on an ancestor toggles dark mode. Both `:root` and `.dark` blocks define every variable. Never use `dark:` variant that depends on `prefers-color-scheme`.

## cn() is mandatory for class composition
All dynamic class merging in Vue components must go through `cn()` from `@/lib/utils` (clsx + tailwind-merge). Never concatenate Tailwind classes with string interpolation or template literals.

## `d-*` custom utilities are the design system
The "Ledger/Atelier" design system exposes custom CSS classes — not Tailwind utilities — for recurring patterns:
- `.d-rule` / `.d-rule-b` — 0.5px hairline borders using `var(--rule)`
- `.d-label` — uppercase mono caption (10px, 600 weight, 0.15em tracking)
- `.d-section` — same as label but accent-colored
- `.d-dots-bg` — dot-grid background texture
- `.d-hover` — 150ms background-color transition to `var(--accent-soft)`
- `.d-arrow-link` / `.d-card-hover` — arrow-nudge and border-deepen on hover/focus-visible
- `.d-press` — 100ms scale(0.99) + translateY(1px) active feedback
- `.d-surface` — white/dark card surface with 0.5px rule border
- `.d-skeleton` — pulsing skeleton block (1.6s ease-in-out)
- `.d-textarea` / `.d-table` — form and dashboard table styles
- `.d-sharp` — force border-radius: 0
Use these instead of writing bespoke Tailwind for the same patterns.

## `.site` and `.prose-site` are public-site only
The `.site` class sets up the flex-column full-height layout and font. `.prose-site` contains all prose typography rules (headings, links, code, tables, blockquotes). These live in raw CSS, not Tailwind — do not replicate them as Tailwind utility stacks in Vue templates.

## Fonts: Bricolage Grotesque + JetBrains Mono via laravel-vite-plugin/fonts
Fonts are loaded through the `bunny()` helper in `vite.config.ts` (Bunny Fonts CDN, privacy-friendly). `--font-sans` and `--font-display` are both Bricolage Grotesque; `--font-mono` is JetBrains Mono. Do not add Google Fonts imports or `<link>` tags for these.

## Border color compatibility layer
The `@layer base` block sets `border-color: var(--color-gray-200, currentColor)` on all elements because Tailwind v4 changed the default border color to `currentColor`. The `* { @apply border-border }` rule then overrides it with the design token. Do not remove the compatibility block.

## Motion: always respect prefers-reduced-motion
All animations and transitions must honor the global `@media (prefers-reduced-motion: reduce)` block that forces `0.01ms` durations. When adding new animations, place them outside that block — the media query already covers them.

## tw-animate-css
The `@import 'tw-animate-css'` line brings in Radix-era enter/leave animation utilities for shadcn components. Do not replace with a custom animation library.
