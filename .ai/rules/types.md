---
paths:
  - 'resources/js/types/**'
---

# Types

## No manual Vue ComponentCustomProperties augmentation; rely on @inertiajs/vue3 types
Do NOT add a `declare module 'vue'` ComponentCustomProperties augmentation for $inertia/$page/$headManager: @inertiajs/vue3's own types already declare these (properly typed), and templates DO use $page (e.g. SiteHeader.vue v-if="$page.props.auth.user"). The old local block referenced unimported Router/Page/createHeadManager and was dead. If a template needs a typed global, import the Inertia types explicitly instead of augmenting.
