---
paths:
  - '{app,resources,routes,tests,database/seeders}/**'
---

# Seeders

## Use Worker terminology for the people module
The people linked to tokens are Workers, not Applicants. Use Worker model/controller/request names, /workers routes, workers relationships, worker_id foreign keys, manage-workers permission names, and Worker labels in all active application code and UI.
