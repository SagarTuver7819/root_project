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

## One-click live DB update (browser URL)

1. Live `.env` ma set karo:

```env
APP_URL=https://your-live-domain.com
MIGRATE_KEY=RootsLiveUpdate2026
DB_HOST=...
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...
```

2. Code upload/pull pachi browser ma aa URL open karo:

```text
https://your-live-domain.com/migrate_live.php?key=RootsLiveUpdate2026
```

Local test:

```text
http://localhost/roots_project/public/migrate_live.php?key=RootsLiveUpdate2026
```

3. Page par **SUCCESS** dekhay pachi:
   - `public/migrate_live.php` delete kari do, **athva**
   - `.env` mathi `MIGRATE_KEY` remove/change kari do

CLI option (SSH): `php database/migrate_live.php`


## Notes for developers

- Server-side listings use `App\Core\DataTable` + `RootsDataTable.init()` in JS.
- AJAX JSON shape: `{ success, message, data, errors }`
- Permissions: `module.action` (example `patients.view`)
- Soft deletes via `deleted_at`
- Appointment double-booking prevented in `AppointmentService`
