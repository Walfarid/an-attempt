# Backend Stack & Infrastructure

Applies to: `compose.yaml`, `docker/**`, `Taskfile.yml`, `phpunit.xml`, `config/filesystems.php`

- Minimum PHP tracks CI/dev runtime (currently 8.5). The Dockerfile pins PHP 8.4 deliberately — Carbon has a known issue on 8.5 (see AGENTS.md); the two are separate and intentional, do not conflate them.
- `phpunit.xml` maps to `.ai/rules/backend-stack.md`.
- Backing services live in `compose.yaml` (MariaDB, Valkey, Mailpit, Garage); the app runs natively on the host. Ports bind to `127.0.0.1` only.
- Image tags are pinned and verified against the Docker Hub registry API at time of use (LTS → stable → latest), noted in the compose header comment.
- Object storage: **Garage** (`dxflrs/garage`, user-pinned commit build; MinIO is stale). Single-node dev mode via `/garage server --single-node --default-bucket` with config at `docker/garage/garage.toml`. S3 API on 3900, Admin API on 3903.
- Media/uploads use the `media` filesystem disk (`s3` driver). Local endpoint is Garage; production targets Oracle Cloud's S3-compatible endpoint — swapping backends must remain a `.env`-only change (AWS_* vars reference GARAGE_* vars).
- Dev database: MariaDB via Docker (`laravel`). Tests also run on MariaDB, against a dedicated `walfa_testing` database created by `docker/mariadb/init/01-testing-database.sql` (fresh volumes) — never run tests against the dev database; `RefreshDatabase` wipes it. Existing volumes don't re-run init scripts, so create the testing DB manually when adopting this on an already-initialized volume.
- `task test` depends on `docker:up`: Docker must be running for the test suite.
- Credentials in `.env.example`/`docker/` configs are local-dev placeholders by convention (see MariaDB/Garage values); never commit real secrets.
