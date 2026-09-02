---
paths:
  - '{app/Http/Requests/TokenRequest.php,app/Http/Controllers/TokenController.php,app/Models/Token.php,app/Models/TokenCategory.php,database/migrations/**,database/seeders/TokenSeeder.php,resources/views/tokens/**,resources/views/pdf/token.blade.php,tests/Feature/TokenControllerTest.php}'
---

# Pdf Feature

## Use category-specific token worker quantities
DLS uses demanded_workers, VA uses required_visa_attestation, and CPA (Change Pre Worker) uses required_worker_changes. Require and retain only the quantity for the selected category, migrate legacy CPA demand counts into required_worker_changes, and render the category-specific label and value in token views and PDFs.
