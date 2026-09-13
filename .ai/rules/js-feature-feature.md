---
paths:
  - '{app/Services/BhcReferenceService.php,app/Http/Controllers/WorkerController.php,resources/views/workers/show.blade.php,resources/views/components/bhc-token-link.blade.php,public/assets/js/app.js,tests/Feature/WorkerBhcTokenLinkTest.php,tests/Feature/BhcTokenLinksTest.mjs}'
---

# Js Feature Feature

## Open all tokens sharing a worker BHC number
Worker detail BHC links open every non-deleted token sharing the normalized BHC in separate tabs, newest received_on then id first. Reuse BhcReferenceService normalization with the worker seeder so prefix, padding, and year variants agree without matching different or missing years. Keep the worker page open, clear child-tab openers, and reveal individual token links if the browser blocks tabs; missing BHC numbers stay plain text.
