# Rules Index

Glob-mapped rules for this repo. Read every file whose globs cover a path before creating or editing files there.

| File | Globs | Topic |
|------|-------|-------|
| [backend-stack.md](backend-stack.md) | `compose.yaml`, `docker/**`, `Taskfile.yml`, `phpunit.xml`, `config/filesystems.php` | Backing services, storage, testing DB |
| [http-conventions.md](http-conventions.md) | `routes/**`, `app/Http/**`, `bootstrap/app.php` | RMM L2 inside Inertia, auth, controllers |
| [content-model.md](content-model.md) | `database/migrations/**`, `app/Models/**`, `database/factories/**` | Content tables, publishing model |
