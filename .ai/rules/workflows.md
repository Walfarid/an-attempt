---
paths:
  - .github/workflows/deploy.yml
---

# Workflows

## OCI SSH ingress must stay open for GitHub runners
The walfa VM security list (ap-singapore-1, "walfa-app-sl") allows SSH 22 from 0.0.0.0/0 because GitHub's `actions` IP ranges (api.github.com/meta, 250+ CIDRs) exceed the security-list rule budget. Do NOT re-lock port 22 to a single IP — deploys die with `dial tcp ...:22: i/o timeout` (runners are silently dropped). sshd is key-only (PasswordAuthentication no). The VM public IP is ephemeral — if it changes after a reboot, update the DEPLOY_HOST secret.
