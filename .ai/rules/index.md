# Project Rules Index

Before planning or editing, find the row whose globs match the file's path and read that rule file.

| Applies to | Rule file |
| --- | --- |
| {resources/views/tokens/index.blade.php,public/assets/css/token-list.css} | .ai/rules/assets-css.md |
| {resources/views/layouts/**,public/assets/css/**} | .ai/rules/css.md |
| {database/seeders/TeamFourWorkerSeeder.php,database/seeders/data/team_4_workers.json,tests/Feature/TeamFourWorkerSeederTest.php} | .ai/rules/data-feature.md |
| {app/Http/Controllers/DocumentController.php,resources/views/tokens/**,tests/Feature/TokenDocumentTest.php,tests/Feature/TokenModalTest.php} | .ai/rules/feature-feature.md |
| {app/Http/Controllers/DocumentController.php,resources/views/tokens/**,tests/Feature/TokenDocumentTest.php}, {app/Http/Controllers/TokenController.php,resources/views/tokens/form.blade.php,tests/Feature/TokenControllerTest.php}, {app/Http/Controllers/TokenController.php,resources/views/tokens/show.blade.php,tests/Feature/TokenControllerTest.php} | .ai/rules/feature.md |
| {app/Services/BhcReferenceService.php,app/Http/Controllers/WorkerController.php,resources/views/workers/show.blade.php,resources/views/components/bhc-token-link.blade.php,public/assets/js/app.js,tests/Feature/WorkerBhcTokenLinkTest.php,tests/Feature/BhcTokenLinksTest.mjs} | .ai/rules/js-feature-feature.md |
| app/Http/Controllers/WorkerController.php,resources/views/workers/form.blade.php,public/assets/js/app.js,tests/Feature/WorkerRoutingTest.php | .ai/rules/js-feature.md |
| {app/Http/Requests/MasterDataRequest.php,app/Models/Company.php,app/Models/Agency.php,tests/Feature/MasterDataContactReuseTest.php} | .ai/rules/models-feature.md |
| app/Http/Controllers/DashboardController.php,app/Models/User.php,resources/views/dashboard.blade.php | .ai/rules/models-views.md |
| {app/Http/Requests/TokenRequest.php,app/Http/Controllers/TokenController.php,app/Models/Token.php,app/Models/TokenCategory.php,database/migrations/**,database/seeders/TokenSeeder.php,resources/views/tokens/**,resources/views/pdf/token.blade.php,tests/Feature/TokenControllerTest.php} | .ai/rules/pdf-feature.md |
| {app/Http/Controllers/TokenController.php,app/Http/Requests/TokenRequest.php,database/migrations/**,database/seeders/TokenSeeder.php,tests/Feature/TokenControllerTest.php} | .ai/rules/seeders-feature.md |
| {app,resources,routes,tests,database/seeders}/** | .ai/rules/seeders.md |
| {app/Http/Controllers/TokenCategoryController.php,app/Http/Requests/TokenCategoryRequest.php,resources/views/layouts/app.blade.php,resources/views/token-categories/**,routes/web.php} | .ai/rules/token-categories.md |
| {app/Http/Requests/TokenRequest.php,app/Http/Controllers/TokenController.php,resources/views/tokens/form.blade.php} | .ai/rules/tokens.md |
| app/Http/Controllers/TokenController.php,resources/views/pdf/token.blade.php,tests/Feature/TokenControllerTest.php | .ai/rules/views-pdf-feature.md |
| app/Http/Controllers/UserController.php,resources/views/users/**,routes/web.php,resources/views/tokens/form.blade.php | .ai/rules/views-tokens.md |
| {app/Http/Controllers/DashboardController.php,resources/views/dashboard.blade.php} | .ai/rules/views.md |
