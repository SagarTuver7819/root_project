# Roots Dental Hospital Management System

Core PHP + MySQL hospital management application for Roots Dental Hospital.

## Stack

- PHP 8.2 (Core PHP MVC, not Laravel)
- MySQL / MariaDB (XAMPP)
- Bootstrap 5, jQuery, DataTables (server-side), Select2, FullCalendar, Toastr, SweetAlert2

## Setup (XAMPP)

1. Ensure Apache + MySQL are running in XAMPP.
2. Project path: `C:\xampp\htdocs\roots_project`
3. Install Composer autoload (already configured):

```bash
cd C:\xampp\htdocs\roots_project
composer dump-autoload -o
```

4. Import schema + seed (if not done):

```bash
C:\xampp\mysql\bin\mysql.exe -u root < database\schema.sql
php database\seed.php
```

5. Open:

[http://localhost/roots_project/public/login](http://localhost/roots_project/public/login)

## Default login

| User | Password |
|------|----------|
| `admin` | `Admin@123` |
| `reception` | `Reception@123` |

## Folder structure

```
app/Core          Router, DB, Auth, DataTable, Controller
app/Controllers   Module controllers
app/Services      Business logic (appointments, audit, ...)
app/Models        Data access helpers
resources/views   Layouts + module UI
public            Front controller + assets
database          schema.sql + seed.php
routes/web.php    All routes
config            app, database, permissions
```

## Theme / branding

Logo-based defaults:

- Primary: `#00AEEF`
- Secondary: `#58595B`

Configure under **Settings → Hospital Profile / Branding**.

## Deploy (GitHub + subdomain)

See steps in chat / below summary:

1. Push code to GitHub (`main` branch).
2. On hosting: clone repo, point subdomain document root to `public/`.
3. Copy `config/database.example.php` → `config/database.php` (live DB).
4. Copy `config/app.example.php` → `config/app.php` (set `url`, `debug=false`).
5. Run `composer dump-autoload -o`, import `database/schema.sql`, then `php database/seed.php`.
6. Run `php database/migrate_booking.php` and `php database/sync_roles.php` if needed.

## Notes for developers

- Server-side listings use `App\Core\DataTable` + `RootsDataTable.init()` in JS.
- AJAX JSON shape: `{ success, message, data, errors }`
- Permissions: `module.action` (example `patients.view`)
- Soft deletes via `deleted_at`
- Appointment double-booking prevented in `AppointmentService`
