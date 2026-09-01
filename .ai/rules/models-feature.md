---
paths:
  - '{app/Http/Requests/MasterDataRequest.php,app/Models/Company.php,app/Models/Agency.php,tests/Feature/MasterDataContactReuseTest.php}'
---

# Models Feature

## Master data contacts are reusable
Phone numbers and email addresses are not unique across companies or agencies. Do not add validation or database uniqueness constraints for these contact fields.
