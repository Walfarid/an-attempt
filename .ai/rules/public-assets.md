---
paths:
  - public/**
  - tools/generate-og-image.php
---

# Public Assets & OG-Image Generation

## OG image

- `public/og-default.png` is 1200×630 PNG (Open Graph standard). Regenerated with
  `php tools/generate-og-image.php` — a one-shot GD script, not a runtime service.
- Colors must match the brand palette: paper `#fafaf5`, ink `#1a1a18`, accent `#17594a`,
  rule/hairline `#e3e2dc`.
- Font is `vendor/laravel/framework/resources/fonts/DejaVuSans-Bold.ttf`. When that
  path is missing (e.g. after a vendor prune), the script silently falls back to GD's
  built-in bitmap font — the output looks wrong but does not error. Verify visually
  after regenerating.
- `og-default.png` is the fallback OG image for the home page, blog posts, and guides.
  Controllers use `$post->cover_url ?? url('/og-default.png')`. Never rename or move it
  without updating `HomeController`, `BlogController`, and `GuideController`.

## Favicons & icons

- Three icon files, configured in `AppServiceProvider::boot()`:
  - `public/favicon.svg` (vector, accent-color W mark, `type: ImageType::Svg`)
  - `public/favicon.ico` (16×16 + 32×32, fallback for browsers that ignore SVG)
  - `public/apple-touch-icon.png` (180×180 PNG RGBA)
- The Blade `<head>` hardcodes `<link rel="icon" href="/favicon.ico" sizes="any">` as
  a fallback alongside the Inertia-rendered head.

## Static files in public/

- `robots.txt` blocks `/dashboard`, `/settings`, `/login`, `/authenticate`.
- `TrackPageView` middleware skips `favicon.ico`, `robots.txt`, and `sitemap.xml`
  (no analytics noise).
- `fonts-manifest.dev.json` is a dev artifact — committed but not used at runtime.
- `.htaccess` sets immutable cache headers: 1 year for hashed Vite assets (js/css/fonts),
  1 week for images.
