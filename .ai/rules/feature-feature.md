---
paths:
  - '{app/Http/Controllers/DocumentController.php,resources/views/tokens/**,tests/Feature/TokenDocumentTest.php,tests/Feature/TokenModalTest.php}'
---

# Feature Feature

## Limit token letter uploads to the edit page
Each token has one demand-letter collection but may have multiple confirmation-letter collections. Existing letters may receive new versions. Creation and version-upload controls belong only on the token edit page; token modals remain preview/download-only.
