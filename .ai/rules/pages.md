---
paths:
  - resources/js/pages/Welcome.vue
---

# Pages

## Home sections are deferred props — keep them streaming
HomeController ships hero+stats+contact eagerly; skills/experiences/projects/educations/publications/posts are Inertia::defer(...)->once(). They arrive on a second request: the frontend shows HomeSkeleton + calls useScrollAnimations refresh() when they land (watch on props). Tests must assert deferred content via assertInertia loadDeferredProps('default', ...), not the initial response. useRouteTransition ignores deferred/partial visits (visit.deferredProps / visit.only) — never blank the root for background syncs.
