# Bangladesh-Brunei Visa & Worker Management System

A Laravel 13 modular-monolith implementation of the Bangladesh High Commission, Brunei Darussalam SRS. It consolidates company and agency master data, token issuance, desk movement, applicant entry, secure document versions, PDF letters, Excel-compatible exports, and immutable audit records.

## Requirements

- PHP 8.3+
- Composer 2
- MySQL or MariaDB for production (SQLite works locally)
- Node.js only when refreshing vendored Bootstrap assets; Vite is not used

## Local setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Seeded administrator: `admin@bhcbrunei.gov.bd` / `ChangeMe123!`. Change this password immediately outside local development.

## Frontend assets

Bootstrap 5 and Bootstrap Icons are committed under `public/assets`, so Node.js is not required to serve the application. To refresh them:

```bash
npm install
npm run assets
```

## Security and storage

- Roles and field-level rules are enforced server-side.
- Applicant uploads use the private `local` disk and authorized download routes.
- Protected token changes, desk movement, document activity, exports, and authentication are audited.
- Companies, agencies, tokens, and applicants use deactivation or soft deletion patterns to preserve history.

Run `php artisan test` before deployment. Configure HTTPS, queue workers, backups, mail, session lifetime, and private storage for the target infrastructure.
