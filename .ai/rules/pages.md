---
paths:
  - resources/js/pages/Welcome.vue
---

# Pages

## Home sections are deferred props — keep them streaming
HomeController ships hero+stats+contact eagerly; skills/experiences/projects/educations/publications/posts are Inertia::defer(...)->once(). They arrive on a second request: the frontend shows HomeSkeleton while waiting. Tests must assert deferred content via assertInertia loadDeferredProps('default', ...), not the initial response. useRouteTransition ignores deferred/partial visits (visit.deferredProps / visit.only) — never blank the root for background syncs.

## Home sections live in an async chunk (HomeSections.vue)
The six below-the-fold sections are extracted to `components/site/HomeSections.vue`, loaded via `defineAsyncComponent` and warmed after first paint (double rAF — a `setTimeout(0)` warm puts the parse inside the boot chain and measurably delays LCP; never reintroduce it). The sections mount only when BOTH the deferred props have landed and the chunk is ready (v-if gate keeps the skeleton — never a blank gap between them). HomeSections emits `contentMounted` when its DOM commits; Welcome calls `useScrollAnimations.refresh()` there (the old props-watch fired pre-commit and the sections' reveal animations raced — always refresh from the child mount event).