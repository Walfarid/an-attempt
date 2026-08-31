---
paths:
  - 'database/migrations/**'
---

# Migrations

## Redundant single-column indexes dropped (Round 14); skills.category kept
page_views.viewed_at and project_screenshots.project_id/sort_order singles were dropped — fully covered by composites (viewed_at,path) and (project_id,sort_order); proven via MariaDB EXPLAIN (identical plans) + write micro-benchmarks on scratch tables (walfa_testing only). Do not re-add without a query pattern that needs them. skills.category_index was KEPT: no canonical (category,name) composite exists in migrations — the skills_category_name_index visible in the dev SQLite DB is uncommitted drift, NOT canonical schema (CI/prod never have it).
