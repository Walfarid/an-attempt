---
paths:
  - 'resources/js/**/*.vue'
---

# Js

## Use formatDate() for short-format dates; detail pages keep their long-month format
Use formatDate() from @/lib/utils (short-month format, e.g. "Jan 5 2024") for all list/index date rendering: posts Index/Tag date props, guides Index, dashboard Posts/Guides publishLabel, HomeSections post cards. Do NOT inline new toLocaleDateString calls with the short format. Intentional exceptions — keep their literal `month:'long'` format (do not switch to formatDate): posts/Show and guides/Show date stamps, Privacy.vue formatUpdated; HomeSections education ended_at uses short-year-only; data/portfolio.ts formatDateRange stays self-contained.
