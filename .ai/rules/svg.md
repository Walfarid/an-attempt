---
paths:
  - 'app/Support/SvgSanitizer.php'
  - 'tests/Unit/Support/SvgSanitizerTest.php'
---

# SVG Sanitizer

## Contract

`App\Support\SvgSanitizer::sanitize()` sanitizes inline SVG content extracted from Markdown before rendering. The sanitizer must:

1. **Never crash on malformed input**: malformed XML, unclosed tags, and input without a root `<svg>` element return `''` instead of throwing exceptions.
2. **Strip dangerous URL schemes**: `javascript:`, `js:`, `data:`, and `vbscript:` prefixes are removed from `href` and `xlink:href` attributes (case-insensitive).
3. **Block external file references**: `<use href="/path/to/file.svg#id">` (external file references) are removed; only internal fragments (`href="#id"`) are allowed.
4. **Reject DOCTYPEs with external entities**: input containing `<!DOCTYPE ... [<!ENTITY ... SYSTEM ...]>` returns `''` to prevent XXE attacks.
5. **Restore libxml state**: the sanitizer must save and restore `libxml_use_internal_errors()` state and clear errors after every call, regardless of input validity.

## Regression pin

`tests/Unit/Support/SvgSanitizerTest.php` is the regression pin. The test suite covers:
- Happy path: preserved elements, attributes, blocked elements (script/foreignObject/animate), event handlers
- Malformed/unclosed/no-root input → `''` without throwing
- Protocol stripping: `javascript:`, `js:`, `data:`, `vbscript:` (case-insensitive) on both `href` and `xlink:href`
- External `<use>` references removed; internal fragments preserved
- DOCTYPE with external entities rejected
- libxml state restoration

## Allowed elements and attributes

The allowlist is defined in `SvgSanitizer::ALLOWED`. Do not expand it without reviewing the security implications — every element/attribute pair is a potential XSS vector.

## Markdown integration

`App\Support\Markdown::toHtml()` extracts `<svg>...</svg>` blocks before CommonMark conversion, passes them through `SvgSanitizer::sanitize()`, and restores them after. The extraction regex requires a closing `</svg>` tag, so unclosed/malformed SVG in Markdown is handled by CommonMark's HTML stripping, not the sanitizer. The sanitizer's malformed-input guards are defense-in-depth for direct callers.
