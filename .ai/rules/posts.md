---
paths:
  - 'resources/js/pages/posts/*.vue'
---

# Posts

## Public index layout follows guides (wide grid, not narrow list)
`/posts` and `/posts/tag` use the same `max-w-6xl` 3-column cover-card grid as `/guides` (`grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3`). Do not shrink them to the old narrow `max-w-3xl` stacked list — the design direction is: posts follow guides. Named layout choices like this are settled; change them only with an explicit user request.

## Posts index/tag payloads must ship cover_url
The wide cards render `post.cover_url`. Both `BlogController::index()` and `BlogController::tag()` select `cover_image_path` and append `cover_url` (then hide the raw path). If you drop it from the payload, covers silently disappear from index/tag cards while the detail page still has them.

## InfiniteScroll data= requires Inertia::scroll() server-side
posts/Index.vue uses <InfiniteScroll data="posts">. The initial page object must carry scrollProps.posts — provided by wrapping the paginator in Inertia::scroll() (inertia-laravel 3.x). If the controller passes a raw paginator, @inertiajs/core throws "The page object does not contain a scroll prop named posts" on every /posts visit (console error, infinite scroll also breaks).
