---
paths:
  - compose.prod.yaml
---

# General

## Queue worker healthcheck must stay disabled; sync compose by scp
The FrankenPHP app image ships a default healthcheck against the Caddy admin port (:2019), so the queue worker container (no Caddy) shows "unhealthy" forever unless `healthcheck: disable: true` is set (as it is). Also: git push does NOT sync compose.prod.yaml to the VM — the file is /opt/walfa/compose.yaml there; after editing, scp it (ssh key: ~/.ssh/id_ed25519_oci, user ubuntu@<VM IP>) and `docker compose up -d --remove-orphans`.
