---
paths:
  - compose.prod.yaml
  - .env.example
---

# General

## Queue worker healthcheck must stay disabled; sync compose by scp
The FrankenPHP app image ships a default healthcheck against the Caddy admin port (:2019), so the queue worker container (no Caddy) shows "unhealthy" forever unless `healthcheck: disable: true` is set (as it is). Also: git push does NOT sync compose.prod.yaml to the VM — the file is /opt/walfa/compose.yaml there; after editing, scp it (ssh key: ~/.ssh/id_ed25519_oci, user ubuntu@<VM IP>) and `docker compose up -d --remove-orphans`.

## Keep session/cache/queue on Redis (Valkey) in .env.example
.env.example must keep SESSION_DRIVER=redis, CACHE_STORE=redis, QUEUE_CONNECTION=redis and REDIS_PERSISTENT=true (Valkey runs in compose.yaml). Downgrading to database reintroduces the round-11 regression: 2-3 extra queries per request plus DB session-table growth. CI copies .env.example to .env (ci.yml) and phpunit.xml overrides drivers for tests, so drift is silent — that is why this rule exists.
