---
paths:
  - 'resources/js/pages/settings/**'
---

# Settings Pages

## Auth middleware: WorkOS session validation, not just `auth`
Settings routes use both `auth` and `ValidateSessionWithWorkOS` middleware (defined in `routes/settings.php`). Any new settings page added inside the group inherits this — do not weaken it to `auth` alone.

## Appearance is client-only, not persisted to the database
Theme preference lives in `localStorage` (client reactivity) and an `appearance` cookie (SSR). The cookie is excluded from encryption in `bootstrap/app.php` so JS can read/write it directly. `HandleAppearance` middleware reads the cookie and `View::share`s it for Blade. There is no `appearance` column on any model and no PATCH endpoint for theme.

## Appearance page uses `Route::inertia()`, not a controller
`Route::inertia('settings/appearance', 'settings/Appearance')` renders the component directly with no props, no controller, and no form submission. If you add props, convert to a controller action — do not inline closures in the route file.

## Profile form uses Wayfinder binding, not manual `useForm`
The Profile page uses `ProfileController.update.form()` (Wayfinder-generated). Do not replace with a manual `useForm()` call. The email field is rendered `disabled` — it displays but never submits; only `name` is updatable.

## Delete account uses WorkOS `AuthKitAccountDeletionRequest`, not a password gate
`ProfileController::destroy` type-hints `AuthKitAccountDeletionRequest` (from `laravel/workos`), which handles the confirmation flow server-side. The `DeleteUser.vue` component wraps it in a `Dialog` (not `AlertDialog`) with `reset-on-success` and `preserveScroll: true`. Do not add a local password field — WorkOS manages identity.

## Success feedback: `Inertia::flash('toast', ...)` in the controller
Profile updates flash a toast via `Inertia::flash('toast', ['type' => 'success', 'message' => ...])`. The frontend `flashToast.ts` picks it up. Do not use session flash or `$request->session()->flash()` directly for user-facing success messages.

## `/settings` redirects to `/settings/profile`
The bare `/settings` path is a redirect, not a page. Always link to named routes (`profile.edit`, `appearance.edit`), never to `/settings`.
