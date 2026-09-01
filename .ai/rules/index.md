# Project Rules Index

Before planning or editing, find the row whose globs match the file's path and read that rule file.

| Applies to | Rule file |
| --- | --- |
| {resources/views/tokens/index.blade.php,public/assets/css/token-list.css} | .ai/rules/assets-css.md |
| {resources/views/layouts/**,public/assets/css/**} | .ai/rules/css.md |
| {app/Http/Controllers/DocumentController.php,resources/views/tokens/**,tests/Feature/TokenDocumentTest.php} | .ai/rules/feature.md |
| {app/Http/Requests/MasterDataRequest.php,app/Models/Company.php,app/Models/Agency.php,tests/Feature/MasterDataContactReuseTest.php} | .ai/rules/models-feature.md |
| {app/Http/Controllers/TokenController.php,app/Http/Requests/TokenRequest.php,database/migrations/**,database/seeders/TokenSeeder.php,tests/Feature/TokenControllerTest.php} | .ai/rules/seeders-feature.md |
| {app,resources,routes,tests,database/seeders}/** | .ai/rules/seeders.md |
| {app/Http/Controllers/TokenCategoryController.php,app/Http/Requests/TokenCategoryRequest.php,resources/views/layouts/app.blade.php,resources/views/token-categories/**,routes/web.php} | .ai/rules/token-categories.md |
| {app/Http/Requests/TokenRequest.php,app/Http/Controllers/TokenController.php,resources/views/tokens/form.blade.php} | .ai/rules/tokens.md |
| {app/Http/Controllers/DashboardController.php,resources/views/dashboard.blade.php} | .ai/rules/views.md |
