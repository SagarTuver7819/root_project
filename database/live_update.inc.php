<?php
/**
 * Shared live DB updater (schema + role permissions).
 * Used by: database/migrate_live.php (CLI) and public/migrate_live.php (URL).
 */

use App\Core\App;
use App\Core\Database;

/**
 * @return list<string>
 */
function roots_live_update(): array
{
    $log = [];
    $log[] = '=== Roots HMS live update ===';
    $log[] = 'DB: ' . (App::config('database')['database'] ?? '?');
    $log[] = 'Time: ' . date('Y-m-d H:i:s');

    $columnExists = static function (string $table, string $column): bool {
        return (bool) Database::fetch("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
    };

    $tableExists = static function (string $table): bool {
        $db = App::config('database')['database'] ?? '';
        return (bool) Database::fetch(
            'SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?',
            [$db, $table]
        );
    };

    // --- Schema ---
    if (!$columnExists('doctors', 'calendar_color')) {
        Database::query("ALTER TABLE doctors ADD COLUMN calendar_color VARCHAR(20) NOT NULL DEFAULT '#00AEEF' AFTER slot_duration");
        $log[] = 'Added doctors.calendar_color';
    } else {
        $log[] = 'OK doctors.calendar_color';
    }

    $palette = ['#F472B6', '#FACC15', '#22C55E', '#14B8A6', '#38BDF8', '#2563EB', '#A855F7', '#6B7280'];
    $doctors = Database::fetchAll('SELECT id, calendar_color FROM doctors WHERE deleted_at IS NULL ORDER BY id ASC');
    $i = 0;
    foreach ($doctors as $doc) {
        $current = strtoupper(trim((string) ($doc['calendar_color'] ?? '')));
        if ($current !== '' && $current !== '#00AEEF') {
            continue;
        }
        $color = $palette[$i % count($palette)];
        Database::update('doctors', [
            'calendar_color' => $color,
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = :_id', ['_id' => (int) $doc['id']]);
        $log[] = "Set doctor #{$doc['id']} color {$color}";
        $i++;
    }

    if (!$tableExists('patient_clinical_charts')) {
        $sqlFile = __DIR__ . '/migrations/patient_clinical_charts.sql';
        if (!is_file($sqlFile)) {
            throw new RuntimeException('Missing migrations/patient_clinical_charts.sql');
        }
        Database::connection()->exec((string) file_get_contents($sqlFile));
        $log[] = 'Created patient_clinical_charts';
    } else {
        $log[] = 'OK patient_clinical_charts table';
    }

    foreach ([
        'next_appointment_id' => 'INT UNSIGNED NULL',
        'implant_appointment_id' => 'INT UNSIGNED NULL',
        'lab_work' => 'LONGTEXT NULL',
        'implant_work' => 'LONGTEXT NULL',
        'on_examination' => 'TEXT NULL',
    ] as $col => $def) {
        if (!$columnExists('patient_clinical_charts', $col)) {
            Database::query("ALTER TABLE patient_clinical_charts ADD COLUMN `{$col}` {$def}");
            $log[] = "Added patient_clinical_charts.{$col}";
        } else {
            $log[] = "OK patient_clinical_charts.{$col}";
        }
    }

    if ($tableExists('bills') && !$columnExists('bills', 'booking_amount')) {
        Database::query('ALTER TABLE bills ADD COLUMN booking_amount DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER gross_amount');
        $log[] = 'Added bills.booking_amount';
    } elseif ($tableExists('bills')) {
        $log[] = 'OK bills.booking_amount';
    }

    if (!$tableExists('patient_suggested_treatments')) {
        Database::connection()->exec(
            "CREATE TABLE IF NOT EXISTS patient_suggested_treatments (
              id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
              patient_id INT UNSIGNED NOT NULL,
              sort_order INT UNSIGNED NOT NULL DEFAULT 1,
              description VARCHAR(255) NOT NULL,
              doctor_id INT UNSIGNED NULL,
              appointment_id INT UNSIGNED NULL,
              created_by INT UNSIGNED NULL,
              updated_by INT UNSIGNED NULL,
              created_at DATETIME NULL,
              updated_at DATETIME NULL,
              INDEX idx_pst_patient (patient_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        $log[] = 'Created patient_suggested_treatments';
    } else {
        $log[] = 'OK patient_suggested_treatments table';
    }

    // --- Role permissions ---
    $now = date('Y-m-d H:i:s');
    $roles = ['super_admin', 'admin', 'receptionist', 'doctor', 'accounts', 'inventory'];
    $roleIds = [];
    foreach ($roles as $slug) {
        $row = Database::fetch('SELECT id FROM roles WHERE slug = ?', [$slug]);
        if (!$row) {
            $log[] = "WARN missing role: {$slug}";
            continue;
        }
        $roleIds[$slug] = (int) $row['id'];
    }

    $permMap = require __DIR__ . '/../config/permissions.php';
    $permissionIds = [];
    foreach ($permMap as $module => $actions) {
        foreach ($actions as $action) {
            $slug = $module . '.' . $action;
            $existing = Database::fetch('SELECT id FROM permissions WHERE slug = ?', [$slug]);
            if ($existing) {
                $permissionIds[$slug] = (int) $existing['id'];
                continue;
            }
            $permissionIds[$slug] = Database::insert('permissions', [
                'module' => $module,
                'action' => $action,
                'slug' => $slug,
                'name' => ucwords(str_replace('_', ' ', $module)) . ' - ' . ucwords(str_replace('_', ' ', $action)),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $log[] = "Created permission {$slug}";
        }
    }

    foreach (['super_admin', 'admin'] as $slug) {
        if (empty($roleIds[$slug])) {
            continue;
        }
        foreach ($permissionIds as $pid) {
            Database::query(
                'INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?, ?)',
                [$roleIds[$slug], $pid]
            );
        }
    }

    $sets = [
        'receptionist' => [
            'dashboard.view',
            'calendar.view', 'calendar.add', 'calendar.edit',
            'appointments.view', 'appointments.add', 'appointments.edit', 'appointments.status_change',
            'queue.view', 'queue.status_change',
            'patients.view', 'patients.add', 'patients.edit', 'patients.print',
            'billing.view', 'billing.add', 'billing.edit', 'billing.print',
            'payments.view', 'payments.add', 'payments.print',
            'outstanding.view',
        ],
        'doctor' => [
            'dashboard.view',
            'calendar.view', 'calendar.add', 'calendar.edit',
            'appointments.view', 'appointments.add', 'appointments.edit', 'appointments.status_change',
            'queue.view', 'queue.status_change',
            'patients.view', 'patients.edit', 'patients.print',
            'visits.view', 'visits.add', 'visits.edit',
            'treatments.view', 'treatments.add', 'treatments.edit', 'treatments.status_change',
            'treatment_sessions.view', 'treatment_sessions.add', 'treatment_sessions.edit',
            'prescriptions.view', 'prescriptions.add', 'prescriptions.edit', 'prescriptions.print',
            'treatment_masters.view',
            'medicine_masters.view',
            'doctors.view',
            'billing.view', 'billing.print',
            'payments.view',
        ],
        'accounts' => [
            'dashboard.view',
            'patients.view', 'patients.print',
            'doctors.view', 'treatment_masters.view',
            'billing.view', 'billing.add', 'billing.edit', 'billing.print', 'billing.approve',
            'payments.view', 'payments.add', 'payments.print', 'payments.edit',
            'outstanding.view', 'outstanding.export',
            'reports.view', 'reports.export', 'reports.print',
        ],
        'inventory' => [
            'dashboard.view',
            'inventory.view', 'inventory.add', 'inventory.edit',
            'suppliers.view', 'suppliers.add', 'suppliers.edit',
            'purchases.view', 'purchases.add', 'purchases.edit',
            'reports.view', 'reports.export',
        ],
    ];

    foreach ($sets as $role => $perms) {
        if (empty($roleIds[$role])) {
            continue;
        }
        Database::query('DELETE FROM role_permissions WHERE role_id = ?', [$roleIds[$role]]);
        foreach ($perms as $slug) {
            if (!isset($permissionIds[$slug])) {
                $log[] = "WARN missing permission slug: {$slug}";
                continue;
            }
            Database::query(
                'INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?, ?)',
                [$roleIds[$role], $permissionIds[$slug]]
            );
        }
        $log[] = strtoupper($role) . ' => ' . count($perms) . ' permissions';
    }

    $log[] = '=== Live update complete ===';
    return $log;
}
