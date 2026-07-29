# IHSA Fisheries Operations

Native Laravel 12 application for fisheries statistics, port operations, workforce management, recruitment, payroll, data quality, and geographic reporting.

## Requirements

- PHP 8.2 or newer
- Composer 2
- MySQL 8+ or MariaDB 10.6+
- Node.js 20+ and npm

## Installation

```bash
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install
npm run build
```

Configure the `DB_*` values in `.env` before running migrations. The seeders install the supported roles, work shifts, and reference fish species.

Point the web server document root at `public/`. With XAMPP, the application can also be served locally with:

```bash
php artisan serve
```

Open `/setup` once to create the first `super_admin` account, then sign in through `/login`.

## Development

```bash
composer run dev
```

The application follows standard Laravel boundaries:

- routes in `routes/web.php`;
- controllers and validated Form Requests in `app/Http`;
- authorization policies in `app/Policies`;
- business workflows in `app/Actions`;
- Eloquent models in `app/Models`;
- Blade views in `resources/views`;
- domain migrations and idempotent seeders in `database`.

## Verification

```bash
php artisan test
vendor/bin/pint --test
php artisan view:cache
composer audit
```

Tests use an in-memory SQLite database and seed the same reference data used by a normal installation.

## Main workspaces

- Executive and regional dashboards
- Governorate and port control rooms
- Trips, catches, discrepancies, and alerts
- Attendance, geographic coverage, HR, and payroll
- Recruitment and employee self-service
- Harbor records, licenses, workers, capacities, and violations
- Twelve role-scoped reports with UTF-8 CSV export

Uploads are stored through Laravel disks and protected attachments are served only after authorization. Do not expose the project root or `storage/` as a public document root.
