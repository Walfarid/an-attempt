---
paths:
  - resources/js/data/portfolio.ts
---

# Portfolio Data Types

Applies to: `resources/js/data/portfolio.ts`

## Source of truth

This file is the **frontend source of truth** for portfolio entity shapes; backend Eloquent models and database tables mirror these types. When adding or renaming a field, update the type here **and** the corresponding migration, model, factory, and Form Request — the Inertia props serialize directly from Eloquent.

## Public vs dashboard prop shapes

Several types split into a public variant and a dashboard variant. Optional fields mark the split — a field that is optional (`?`) is present only on dashboard responses and absent on public homepage responses:

- `Project.slug?`, `Project.image_tone?`, `Project.featured?`, `Project.sort_order?`, `Project.published_at?`
- `Profile.bio?`, `Profile.avatar_path?`, `Profile.bio_html?`
- `Post.body?`, `Post.excerpt?`, `Post.tags?`

When consuming these types, guard on the optional field or use a narrowed type (`PublicPost`, `PublicPostDetail`) — do not assume dashboard fields exist on public pages.

## Distinct entity variants

Do not conflate the similarly-named types; each serves a different surface:

| Base | Dashboard | Public index | Public detail |
|------|-----------|-------------|---------------|
| Post | `Post` (body optional) | `PublicPost` | `PublicPostDetail` |
| Guide | `Guide` (body + posts?) | `PublicGuide` | `PublicGuideDetail` |

`GuideListItem` is a separate dashboard-index row type (no body). `PostOption` is for the related-posts picker only.

## SkillCategory enum sync

`SkillCategory` values (`languages`, `frameworks`, `databases`, `devops`, `platform`, `security`) must match `App\Enums\SkillCategory` on the backend exactly. Adding or renaming a category requires updating both sides.

## ProjectScreenshot

The public type has only `{ alt, url }`. The dashboard re-adds `id` for screenshot deletion — do not add `id` to the shared type.

## Media type

`Media` represents a dashboard upload stored on the `media` (S3) disk. `url` can be null for pending uploads. `size` is in bytes.

## formatDateRange

The sole runtime helper. Uses `en-US` locale with `{ month: 'short', year: 'numeric' }`. `end = null` renders as "Present".

## Dependency scope

Imported by 23+ files across pages, layouts, and components. Renaming or restructuring a type here is a broad change — verify with `vue-tsc` and the test suite.
