---
paths:
  - app/Http/Requests/Dashboard/EducationRequest.php
  - app/Http/Requests/Dashboard/ExperienceRequest.php
  - app/Http/Requests/Dashboard/GuideRequest.php
  - app/Http/Requests/Dashboard/ProjectRequest.php
  - app/Http/Requests/Dashboard/PublicationRequest.php
  - app/Http/Requests/Dashboard/SkillRequest.php
  - app/Http/Requests/Dashboard/StoreMediaRequest.php
  - app/Http/Requests/Dashboard/StoreScreenshotRequest.php
  - app/Http/Requests/Dashboard/UpdatePrivacyPolicyRequest.php
  - app/Http/Requests/Dashboard/UpdateProfileRequest.php
  - app/Http/Requests/Dashboard/UploadCoverRequest.php
  - app/Http/Requests/Dashboard/UploadGuideCoverRequest.php
  - app/Http/Requests/Settings/ProfileUpdateRequest.php
---

# Form Requests

## No `authorize()` — auth belongs to middleware
None of the form requests override `authorize()`. Authorization is enforced by route middleware, not inside requests. Do not add `authorize()` methods.

## Nullable-array pattern
Optional array fields use `'nullable', 'array'` on the parent key and `'required', 'string'` (or `'integer', 'exists:…'`) on the wildcard child. `nullable` on the parent means the field can be absent or null; `required` on `field.*` means every element must pass once the array is present.

## `prepareForValidation` for slug derivation
ProjectRequest and GuideRequest both derive the slug from the title via `prepareForValidation()`: `$this->merge(['slug' => Str::slug($this->input('slug', $this->input('title', '')))])`. Keep this pattern when adding new slug-bearing models; the slug field is still validated as `required` + `alpha_dash` + `unique`.

## File upload rules
- **Raster images** (covers, screenshots): `'required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'`.
- **StoreMediaRequest** uses a custom closure: it whitelists extensions (jpg, jpeg, png, webp, gif, avif, svg) for all uploads to block polyglot uploads, then for SVGs runs `SvgSanitizer::sanitize()` and rejects if it returns empty (malformed). Non-SVG files go through the standard `image` rule. Do not simplify this to a plain `image` rule — it would break SVG uploads.

## Unique-rule scoping (SkillRequest)
Skill names are unique per category, not globally. The rule uses `Rule::unique('skills')->where(fn ($q) => $q->where('category', $this->input('category')))->ignore($this->route('skill'))`. Do not change to a plain `unique` without also updating the model and tests.

## Dynamic year bounds
PublicationRequest uses `'max:'.(now()->year + 1)` for the year ceiling. This is the only request with a dynamic bound; keep it consistent if other requests need year fields.
