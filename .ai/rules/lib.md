---
paths:
  - resources/js/lib/flashToast.ts
---

# Lib

## Keep vue-sonner a lazy import
flashToast must NOT statically import vue-sonner — that would put the ~22.6 KB toast runtime back into the app entry chunk on every public page. The flash handler uses `await import('vue-sonner')` (cached after first toast); the dashboard's Sonner wrapper statically imports the same chunk, so dashboard behavior is unchanged.
