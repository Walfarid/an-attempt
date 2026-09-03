---
paths:
  - 'app/Support/Markdown.php'
  - 'tests/Unit/Support/MarkdownTest.php'
---

# Markdown Renderer

## Contract

`App\Support\Markdown::toHtml()` converts Markdown to HTML using League CommonMark (GFM). All user-authored Markdown in the app flows through this single entry point.

## Configuration (do not change without security review)

- `html_input => 'strip'`: all raw HTML is stripped by CommonMark before rendering. SVG is the only exception — it is extracted before conversion and re-injected after sanitization.
- `allow_unsafe_links => false`: `javascript:`, `data:`, `vbscript:` URLs in links/images are suppressed.
- `max_nesting_level => 20`: bounds recursive structures to prevent pathological input.

## SVG handling

Inline `<svg>…</svg>` blocks are extracted via regex **before** CommonMark conversion, passed through `SvgSanitizer::sanitize()`, replaced with `SVG_BLOCK_N_END` placeholders, then restored into the final HTML. This is necessary because CommonMark would otherwise escape or strip the SVG markup. The extraction regex requires a closing `</svg>` — unclosed SVG is handled by CommonMark's HTML stripping instead.

## Preprocessor

Two malformed-input fixes run before CommonMark:

1. **Bold-wrapped headings/HRs**: `**## Heading**` → `## Heading`, `**---**` → `---`. Content editors sometimes wrap these in bold markers, which causes CommonMark to render `<strong>` instead of `<h2>`/`<hr>`. The regex only fires when the entire line is `**`-wrapped, so normal inline bold is unaffected.
2. **Table blank-line collapse**: contiguous `|`-prefixed rows separated by blank lines are collapsed so GFM tables with accidental blank separators still parse.

## In-memory cache

A static array caches rendered output keyed by raw Markdown input, capped at 256 entries with FIFO half-eviction. This matters under Octane (long-running process) — repeated renders of the same content skip the converter entirely. The cache is per-process, not shared across workers.

## Converter singleton

`GithubFlavoredMarkdownConverter` is lazily instantiated once into a static property. Do not create new converter instances per call — the constructor is expensive.

## Callers

- `Post::bodyHtml()` — blog post body
- `Guide::bodyHtml()` — guide body
- `PrivacyPolicy::bodyHtml()` — privacy policy body
- `Profile::bioHtml()` — profile bio
