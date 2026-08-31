---
paths:
  - 'resources/js/pages/posts/*.vue'
---

# Posts

## InfiniteScroll data= requires Inertia::scroll() server-side
posts/Index.vue uses <InfiniteScroll data="posts">. The initial page object must carry scrollProps.posts — provided by wrapping the paginator in Inertia::scroll() (inertia-laravel 3.x). If the controller passes a raw paginator, @inertiajs/core throws "The page object does not contain a scroll prop named posts" on every /posts visit (console error, infinite scroll also breaks).
