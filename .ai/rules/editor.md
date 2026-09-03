---
paths:
  - 'resources/js/components/editor/**'
  - resources/js/pages/dashboard/Profile.vue
  - resources/js/pages/dashboard/Posts.vue
  - resources/js/pages/dashboard/Guides.vue
  - resources/js/pages/dashboard/PrivacyPolicy.vue
---

# Editor

## MarkdownEditor stays async — never import it eagerly
The Tiptap stack behind `components/editor/MarkdownEditor.vue` is a single ~457 KB / ~143 KB gz chunk. It MUST be loaded via `defineAsyncComponent(() => import('@/components/editor/MarkdownEditor.vue'))` in every consumer (Profile, Posts, Guides, PrivacyPolicy). A static `import MarkdownEditor from '...'` drags Tiptap + ProseMirror onto the initial parse of every dashboard page, even when the editor is not on screen (list views, profile info section). The chunk itself is unchanged; only the fetch timing moves from "page load" to "editor mount". Do not collapse the async wrapper back to a static import, even to "simplify" the file.

## MediaController::index() is the only dual-response controller
`index()` does content negotiation: `$request->wantsJson()` returns a plain JSON array (consumed by the editor's `ImagePickerDialog` via `fetch()`), while Inertia visits and plain browser navigation render the `dashboard/Media` Inertia page. This is the only controller in the application with dual response modes. Do not split it into two endpoints or remove the JSON branch — the image picker depends on it.
