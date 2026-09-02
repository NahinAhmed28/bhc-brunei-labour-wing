---
paths:
  - '{app/Http/Controllers/DocumentController.php,resources/views/tokens/**,tests/Feature/TokenDocumentTest.php}'
  - '{app/Http/Controllers/TokenController.php,resources/views/tokens/form.blade.php,tests/Feature/TokenControllerTest.php}'
---

# Feature

## Allow multiple token confirmation letters
A token may have multiple confirmation letters. Each letter has its own collection_key and version history; adding creates a new collection, while editing creates a new version within only that collection.

## Keep token PDF accessible from edit
The token edit-page header must show a View Token PDF action that opens the named tokens.pdf route in a new tab. Sanitize the response filename separately from the displayed reference so legacy or user-entered separators cannot prevent the inline PDF response from loading.
