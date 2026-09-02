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
