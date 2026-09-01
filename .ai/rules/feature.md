---
paths:
  - '{app/Http/Controllers/DocumentController.php,resources/views/tokens/**,tests/Feature/TokenDocumentTest.php}'
---

# Feature

## Allow multiple token confirmation letters
A token may have multiple confirmation letters. Each letter has its own collection_key and version history; adding creates a new collection, while editing creates a new version within only that collection.
