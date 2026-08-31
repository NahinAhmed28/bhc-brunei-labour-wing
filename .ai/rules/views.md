---
paths:
  - '{app/Http/Controllers/DashboardController.php,resources/views/dashboard.blade.php}'
---

# Views

## Restrict recent activity to super administrators
Only super-admin users may query or see the dashboard Recent activity panel. For every other role, do not fetch audit-log rows and render the remaining dashboard content at full width.
