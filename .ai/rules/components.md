---
paths:
  - resources/js/components/CookieConsentBanner.vue
---

# Components

## Keep the consent banner off the ui/* component graph
CookieConsentBanner boots eagerly in app.ts. It must NOT import ui/* components: ui/button pulls reka-ui Primitive + cva/cn, which forced the 130 KB reka-ui and 28 KB utils chunks onto every public page. The banner uses plain <button>s whose classes mirror buttonVariants('sm') — keep it that way.

## Consent banner names all tracked services explicitly
The consent banner mentions specific third-party services by name (Clarity, GA4, Google AdSense). When adding or removing tracked services, update the banner text to keep the disclosure accurate. The banner must not import ui/* components (see components.md).

## Consent banner also discloses the always-on Cloudflare Web Analytics
The banner adds "Cloudflare Web Analytics always runs as part of the hosting." to the service list, because Cloudflare RUM is not consent-gated. There is no client-side toggle for it; the disclosure lives in the privacy policy's "Always-on infrastructure" section. Keep this line in sync with the policy wording when either changes.
