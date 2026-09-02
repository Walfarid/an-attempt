---
paths:
  - config/cache.php
---

# Config

## Default cache store is redis (Valkey); keep it in sync with .env
Cache default store is 'redis' (Valkey container) — .env CACHE_STORE=redis. Tests use the array store via phpunit.xml. Do not revert to 'database' and do not add a second cache layer; application code uses Cache::remember/forget keys 'profile.name', 'sitemap.xml', 'sitemap.last_modified'.
