---
paths:
  - resources/views/app.blade.php
---

# Views

## Ezoic ads: config-gated, consent-gated, post page only
Ezoic ads (services.ezoic.*) are opt-in via EZOIC_ENABLED + EZOIC_PLACEHOLDER_ID. The sa.min.js + ezstandalone.cmd head scripts render only when consent=accepted AND the page component is posts/Show; the Gatekeeper CMP scripts are intentionally omitted because the site runs its own consent banner. The slot lives in posts/Show.vue via components/site/PostAdSlot.vue — ONE slot per page: a sticky right gutter on xl+ (post-layout--gutter grid) and end-of-article stacked below xl, so only a single ezoic-pub-ad-placeholder-{id} div ever exists (Ezoic: duplicate placeholder IDs = unpredictable ad behaviour). PostAdSlot renders that div unstyled (Ezoic docs: styling the placeholder itself causes empty gaps), gates ALL Ezoic code behind the consent ref from useConsent (privacy policy promises nothing runs pre-consent), injects sa.min.js itself for SPA navigations (the blade head was rendered for the previous page), and flips its "Please allow ads" note via MutationObserver when a fill lands. ads.txt 301s to srv.adstxtmanager.com only when enabled. Test env: phpunit.xml makes EZOIC_ENABLED=false the default; cookie tests must use withUnencryptedCookie (withCookie double-encrypts exempt cookies, so HandleConsent never sees 'accepted').
