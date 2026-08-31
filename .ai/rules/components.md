---
paths:
  - resources/js/components/CookieConsentBanner.vue
---

# Components

## Keep the consent banner off the ui/* component graph
CookieConsentBanner boots eagerly in app.ts. It must NOT import ui/* components: ui/button pulls reka-ui Primitive + cva/cn, which forced the 130 KB reka-ui and 28 KB utils chunks onto every public page. The banner uses plain <button>s whose classes mirror buttonVariants('sm') — keep it that way.
