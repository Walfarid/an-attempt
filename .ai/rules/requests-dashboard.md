---
paths:
  - app/Http/Requests/Dashboard/PostRequest.php
---

# Requests Dashboard

## PostRequest rules must stay standalone-validator compatible
tests/Unit/Http/Requests/PostRequestTest.php builds Validator::make($data, (new PostRequest)->rules()) directly against arbitrary payloads — never use 'present' (or any rule that fails on missing keys) in PostRequest; use 'nullable' + prepareForValidation normalization, and a controller-side default (validated('tags', [])). The 'present' rule broke 21 unrelated unit tests.
