---
paths:
  - app/Support/Markdown.php
---

# Support

## Diagram embeds via @@diagram name@@ token
Post bodies support `@@diagram <name>@@` (lowercase a-z0-9- only); Markdown::toHtml renders it as `<iframe src="/diagrams/<name>" class="diagram-embed">`. The file is uploaded per post from the dashboard (PostDiagramController) to the `media` disk; `GET /diagrams/{slug}` (DiagramController) streams it back with CachePublicResponses. Raw HTML stays stripped; only this token emits HTML. Teaser fallback strips the token. The HTML is generated with the archify skill (see /home/walfa/.agents/skills/archify) and uploaded — no git involved.
