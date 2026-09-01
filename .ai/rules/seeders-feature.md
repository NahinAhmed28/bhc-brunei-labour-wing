---
paths:
  - '{app/Http/Controllers/TokenController.php,app/Http/Requests/TokenRequest.php,database/migrations/**,database/seeders/TokenSeeder.php,tests/Feature/TokenControllerTest.php}'
---

# Seeders Feature

## Token references are reusable
token_number is a reusable reference, not a unique identifier. Multiple token records may share it; use the primary key for identity and keep legacy seeding idempotent with a broader record key.
