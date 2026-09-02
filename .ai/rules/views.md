---
paths:
  - resources/views/app.blade.php
---

# Views

## Ezoic ads: config-gated, consent-gated, post page only
Ezoic ads (services.ezoic.*) are opt-in via EZOIC_ENABLED + EZOIC_PLACEHOLDER_ID. The sa.min.js + ezstandalone.cmd head scripts render only when consent=accepted AND the page component is posts/Show; the Gatekeeper CMP scripts are intentionally omitted because the site runs its own consent banner. The placeholder slot lives in Posts/Show.vue via components/site/PostAdSlot.vue (gutter on lg+, inline end-of-article below lg, "Please allow ads" note when empty). ads.txt 301s to srv.adstxtmanager.com only when enabled. Test env: phpunit.xml makes EZOIC_ENABLED=false the default.
