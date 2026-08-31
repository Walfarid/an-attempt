---
paths:
  - app/Http/Controllers/BlogController.php
---

# Controllers

## Post show fetches post + recent in one driver-aware query
The "Keep reading" list is inlined into the post SELECT as a JSON aggregate (recentSql()). SQLite lacks JSON_ARRAYAGG and treats double-quoted JSON labels as identifiers, so the helper branches per driver (json_group_array vs JSON_ARRAYAGG+COALESCE) and always uses single-quoted labels. The route is posts/{post} with a manual slug lookup (no model binding — that would add a second query); keep the param name `post` or regenerate Wayfinder. Local dev is SQLite, tests/CI run MariaDB — both branches are covered by the suite.
