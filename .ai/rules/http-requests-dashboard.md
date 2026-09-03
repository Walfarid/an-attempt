---
paths:
  - app/Http/Controllers/Dashboard/MediaController.php
  - app/Http/Requests/Dashboard/StoreMediaRequest.php
---

# Http Requests Dashboard

## SVG uploads are sanitized before storage
SVG uploads are sanitized before storage: StoreMediaRequest rejects malformed SVGs (SvgSanitizer returns ''), and MediaController re-runs SvgSanitizer on the stored content, replacing it with the sanitized version. Allowed extensions are whitelisted in the request closure (jpg, jpeg, png, webp, gif, avif, svg) to block polyglot uploads like shell.php with image/svg+xml MIME. The controller's ValidationException for empty sanitization is defense-in-depth.
