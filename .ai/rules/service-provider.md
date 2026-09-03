---
paths:
  - app/Providers/AppServiceProvider.php
---

# AppServiceProvider

## All work belongs in boot(); register() stays empty
No custom bindings or singletons — the provider only calls framework configurators (`Date::use`, `DB::prohibitDestructiveCommands`, `Password::defaults`, `Head::defaults`, `Head::inertiaGlobals`). All of these are safe to call in `boot` and must NOT move to `register`, because they depend on other providers having already registered their facades.

## CarbonImmutable is the global date default
`Date::use(CarbonImmutable::class)` means every `now()`, `$model->created_at`, and Carbon resolution returns an immutable instance. Code that mutates dates in place (`$date->addDay()` expecting `$date` to change) silently produces a new object — always reassign: `$date = $date->addDay()`.

## Password rules are environment-gated; tests see the weak set
Production requires 12 chars + mixed case + numbers + symbols; every other environment accepts 8 chars minimum. When writing request validation that calls `Password::defaults()`, remember the factory closure checks `app()->isProduction()` at call time — Pest runs in `testing`, so the weak rule applies. Do not hardcode the production rule in form requests; always use `Password::defaults()`.

## Destructive DB commands are blocked in production
`DB::prohibitDestructiveCommands(true)` in production prevents `migrate:fresh`, `migrate:reset`, `db:wipe`. This is a runtime guard — it does not affect testing (phpunit.xml uses a separate connection). Do not wrap or disable this guard.

## Head::defaults vs Head::inertiaGlobals — different rendering paths
`Head::defaults()` sets the per-page SEO fallbacks consumed by the `@head` Blade directive (title suffix, OG tags, canonical, Twitter card, robots). `Head::inertiaGlobals()` sets document-level meta that apply once to the SPA shell (viewport, color-scheme, favicon, apple-touch-icon). Per-page overrides go in controllers via `Head::title()`, `Head::description()`, etc. — they merge on top of the defaults, not replace them.
