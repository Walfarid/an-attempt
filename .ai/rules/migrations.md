---
paths:
  - 'database/migrations/**'
---

# Migrations

## Redundant single-column indexes dropped (Round 14); skills.category kept
page_views.viewed_at and project_screenshots.project_id/sort_order singles were dropped — fully covered by composites (viewed_at,path) and (project_id,sort_order); proven via MariaDB EXPLAIN (identical plans) + write micro-benchmarks on scratch tables (walfa_testing only). Do not re-add without a query pattern that needs them. skills.category_index was KEPT: no canonical (category,name) composite exists in migrations — the skills_category_name_index visible in the dev SQLite DB is uncommitted drift, NOT canonical schema (CI/prod never have it).

## Pivot tables need an index on every FK column, not only the composite PK leading edge
Every BelongsToMany pivot needs an index on BOTH FK columns, not just the composite PK's leading edge: guide_post has PK (guide_id, post_id) so Post::guides() (WHERE post_id = ?) full-scans without an extra index — one was added as guide_post_post_id_index (2026_09_02_165148), mirroring project_skill_skill_id_index. When creating any new pivot (or a FK that is queried as the leading column), add an explicit index on the second FK column too.
