---
paths:
  - 'app/Models/Guide.php'
  - 'app/Http/Controllers/Dashboard/GuideController.php'
  - 'app/Http/Controllers/Dashboard/GuideCoverController.php'
  - 'app/Http/Controllers/GuideController.php'
  - 'app/Http/Requests/Dashboard/GuideRequest.php'
  - 'app/Http/Requests/Dashboard/UploadGuideCoverRequest.php'
  - 'resources/js/pages/dashboard/Guides.vue'
  - 'resources/js/pages/guides/**'
---

# Guides

Guides are tutorial-style content with step-by-step markdown body, prerequisites, and estimated time. They parallel posts but are a separate content type with bidirectional cross-references (guide ↔ post via `guide_post` pivot).

## Content model
- Slugs are the public identifier, integer IDs internal-only.
- Nullable `published_at` = publish model (NULL = draft, future date = scheduled).
- Cover images store paths on the `media` disk, never absolute URLs.
- Dashboard CRUD is dialog-style: index/show/store/update/destroy only. One Form Request per entity.
- Guide bodies are Markdown rendered via `App\Support\Markdown::toHtml()`. Step sections use `## Step N: Title` headings.

## Public pages
- `/guides` listing: paginated, newest first, published only.
- `/guides/{slug}` detail: manual slug lookup (no model binding), abort_unless published.
- Related posts shown on guide detail via `guide_post` pivot.

## Cross-references
- `Guide::posts()` ↔ `Post::guides()` via `guide_post` pivot (cascadeOnDelete both sides).
- Dashboard form includes a post multiselect (`PostPicker`) for linking; the store/update controller syncs `validated('posts', [])`.