# Naoshin Enterprise ERP & Workforce Management System

Core PHP MVC ERP for Naoshin Enterprise. It manages workers, foremen, projects, attendance, payroll, expenses, materials, food, vehicle rental, equipment, client payments, applications, reports, notifications, audit logs, settings, backups, public homepage content, and company-branded ID cards.

## Requirements

- PHP 8.2+
- MySQL 8+
- Apache, XAMPP, LAMPP, or PHP built-in server
- PDO MySQL extension
- `mysqldump` for admin database backups

## Installation

1. Copy `.env.example` to `.env`.
2. Create a MySQL database named `contracting_erp`.
3. Import `database/schema.sql`.
4. Import `database/seed.sql`.
5. Update `.env` with your database credentials and `APP_URL`. Bangla is the default language.
6. Ensure these directories are writable: `storage/logs`, `storage/backups`, `public/uploads/workers`, `public/uploads/cheques`, `public/uploads/invoices`, `public/uploads/homepage`, `public/uploads/idcards`.
7. Open `http://localhost/contracting-company-erp/public/login`.

## XAMPP Windows Setup

1. Place the project folder in `C:\xampp\htdocs\contracting-company-erp`.
2. Start Apache and MySQL.
3. Open phpMyAdmin, create `contracting_erp`, then import schema and seed SQL files.
4. Set `APP_URL=http://localhost/contracting-company-erp/public` in `.env`.
5. Login from `/public/login`.

## LAMPP or Ubuntu Setup

1. Copy the folder to your web root, for example `/var/www/contracting-company-erp`.
2. Run `sudo chown -R www-data:www-data storage public/uploads`.
3. Create the database and import SQL files:

```bash
mysql -u root -p -e "CREATE DATABASE contracting_erp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
mysql -u root -p contracting_erp < database/schema.sql
mysql -u root -p contracting_erp < database/seed.sql
```

4. Point Apache document root to `public/` or use the included `.htaccess`.

For an existing installation, import the preservation migration after backup:

```bash
mysql -u root -p contracting_erp < database/migrations/2026_08_08_nousin_enterprise_update.sql
mysql -u root -p contracting_erp < database/migrations/2026_08_16_worker_id_cards_reports_update.sql
```

## PHP Built-In Server

```bash
php -S localhost:8000 -t public
```

Set `APP_URL=http://localhost:8000` in `.env`.

## Default Demo Credentials

All demo accounts use `ChangeMe123!`.

- Admin: `admin@example.com` or `01700000000`
- Foreman: `01800000000`
- Labor: `01900000000`

Seeded demo passwords are stored as one-way bootstrap hashes and are upgraded to PHP `password_hash()` hashes on first successful login.

## Implemented Modules

- Secure login, logout, session timeout, language preference, theme preference, login history
- RBAC for admin, foreman, and labor
- Bilingual English/Bangla UI through centralized language files, Bangla by default
- Public Naoshin Enterprise homepage with hero, recent updates, photo/video gallery, about, services, projects, contact, logo, and login option
- Admin homepage management for hero/about/contact content, updates, services, photos, videos, and logo/media
- Workforce and worker profile management with secure image upload checks
- Automatic worker ID number and ID card generation for foreman/labor
- Admin worker profile view and ID card view/edit/delete/print/download
- Foreman/labor own ID card view/download only
- Project CRUD, project worker assignment history, and project financial calculations
- Attendance with automatic salary and overtime calculation
- Salary transactions, advance, withdrawal, bonus, deduction, and worker balance
- Applications with status tracking
- Daily expenses with invoice image upload/view/download
- Raw material expenses with invoice image and carrying cost
- Food expenses with carrying cost and total cost
- Vehicle/car rental expenses
- Client received payment ledger and receivable calculation
- Admin personal expense account separated from project/company ledgers
- Equipment inventory, assignment, movement history, and available quantity updates
- Reports with filters, print/PDF via print dialog, Excel, and CSV export
- DataTables search, pagination, sorting, responsiveness, and export controls
- Notifications, audit logs, login history, settings, users, roles, and backup history
- CSRF protection, output escaping, prepared statements, secure sessions, upload validation, soft-delete/void policy

## Important Accounting Rules

The application keeps these concepts separate:

- Project contract amount
- Client received money
- Project costs and expenses
- Worker salary
- Worker advance
- Worker withdrawal
- Admin personal expense

Calculated totals are generated from database records through `app/Services/CalculationService.php`.

Current final calculation:

- Total Earning = Total Projects Balance
- Total Expense = Total Projects Expense
- Remaining Balance = Total Earning - Total Expense
- Admin Personal Expense is shown separately and does not affect the calculation
- Projects Received Payment is shown separately and does not affect the calculation

## Language Switching

Use the বাংলা/English buttons on the homepage, login page, or top navbar. UI translations live in `languages/en.php` and `languages/bn.php`. Business data supports bilingual columns where useful, such as project names, descriptions, work types, expense descriptions, homepage content, services, updates, and equipment names.

## Backup

Admin users can create and download database backups from the Backup module. The server must have `mysqldump` available in PATH.

## Troubleshooting

- Database connection error: verify `.env` DB credentials and that MySQL is running.
- Upload error: verify file type is JPG, JPEG, PNG, or WEBP and less than 2 MB.
- Blank page in production: set `APP_DEBUG=true` temporarily and check `storage/logs/app.log`.
- Backup failed: verify `mysqldump` is available and DB credentials are correct.

01706759471
N@01706759471n