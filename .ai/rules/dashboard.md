---
paths:
  - 'resources/js/pages/dashboard/**'
---

# Dashboard

## Dashboard deletes: AlertDialog confirm + optimistic rollback
Never window.confirm. Pattern: open ui/alert-dialog with the entity, then form.delete(url, { preserveScroll: true, optimistic: (props) => ({ <propName>: (props.<propName> as T[] ?? []).filter(x => x.id !== target.id) }) }). Inertia v3 rolls back automatically on 422/errors/interruptions; success toasts come from controllers via Inertia::flash('toast', ...) (already wired in flashToast.ts). 6 CRUD pages + screenshots + post cover + post diagram follow this.
