---
paths:
  - '{database/seeders/TeamFourWorkerSeeder.php,database/seeders/data/team_4_workers.json,tests/Feature/TeamFourWorkerSeederTest.php}'
---

# Data Feature

## Import Team 4 workers only against supplied references
Use the supplied BOESL Confirmation Ref. No. as the BHC/reference; never infer a token from company names. Normalize optional BHC prefixes, numeric padding, and two-/four-digit years without discarding the year. For shared BHC references, the user chose the latest non-deleted token by received_on then id descending. Skip missing/unmatched or conflicting references; preserve existing and soft-deleted passports. Keep source arrival and BOESL confirmation dates in remarks rather than flight dates.
