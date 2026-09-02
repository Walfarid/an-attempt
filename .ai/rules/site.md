---
paths:
  - 'resources/js/components/site/**'
---

# Site

## gsap stays OFF the eager boot graph — lazy/CSS only
PageDrawLoader.vue and useRouteTransition.ts must never statically import gsap (or ScrollTrigger): a static import puts the ~110 KB raw / ~40 KB gz gsap chunk back on every public page's boot chain. Round 16 removed it: the loader's top bar is pure CSS transitions (power2.out = cubic-bezier(0.25,0.46,0.45,0.94), power2.inOut = cubic-bezier(0.455,0.03,0.515,0.955)), the boot drawing uses WAAPI (identical timeline), and useRouteTransition dynamic-imports gsap on first navigation. Homepage scroll/hero/count-up composables and Dashboard already import gsap via dynamic import — keep it that way.

## Eager boot chain: inline site icons, never @lucide/vue
The public boot chain (Welcome, SiteHeader, SiteFooter, posts Index/Show) imports inline SVG icons from components/site/icons.ts, NOT @lucide/vue. A static lucide import anywhere in that chain drags the ~11 KB lucide chunk (with all HomeSections/dashboard icons) onto every public page's boot. Icon path data in icons.ts must mirror lucide exactly (24px viewBox, stroke-width 2, round caps/joins) — copy from the lucide chunk, never freehand. HomeSections + dashboard may keep @lucide/vue (lazy).

## Ezoic showAds targets #ezoic-pub-ad-placeholder-{id}
ezstandalone.showAds(id) injects the ad into the element with id="ezoic-pub-ad-placeholder-{id}" — no such div means no ad ever renders, regardless of adblock/consent/config. That div must stay unstyled and permanently in the DOM (Ezoic docs: styling it or reserving space causes empty gaps). Style the wrapper aside instead. Only one slot per placeholder ID per page.
