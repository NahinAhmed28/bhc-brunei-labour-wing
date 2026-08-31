---
paths:
  - '{app/Http/Requests/TokenRequest.php,app/Http/Controllers/TokenController.php,resources/views/tokens/form.blade.php}'
---

# Tokens

## Limit worker demand fields to DLS tokens
Treat token category code DLS as Demand Letter Submission. Only DLS tokens may accept or retain demanded_workers and pre_selected; hide and discard those fields for every other category. Category code VA continues to use required_visa_attestation.
