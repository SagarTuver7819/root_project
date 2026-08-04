<?php
/**
 * Sync role permissions for new hospital flow.
 * Front Desk: Add patient, queue, calendar, billing only.
 * Doctor: clinical + treatments + calendar.
 * Usage: php database/sync_roles.php
 */
require dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\App;
use App\Core\Database;

App::bootstrap();
$now = date('Y-m-d H:i:s');

$roles = ['super_admin', 'admin', 'receptionist', 'doctor', 'accounts', 'inventory'];
$roleIds = [];
foreach ($roles as $slug) {
    $row = Database::fetch('SELECT id FROM roles WHERE slug = ?', [$slug]);
    if (!$row) {
        fwrite(STDERR, "Missing role: {$slug}\n");
        exit(1);
    }
    $roleIds[$slug] = (int) $row['id'];
}

$permMap = require dirname(__DIR__) . '/config/permissions.php';
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
    }
}

foreach (['super_admin', 'admin'] as $slug) {
    foreach ($permissionIds as $pid) {
        Database::query('INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?, ?)', [$roleIds[$slug], $pid]);
    }
}

$sets = [
    // Front Desk: Patient → Send to doctor → Calendar → Billing after treatment
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
    // Doctor: Queue → Clinical chart → Book next appt → Treatment
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
    Database::query('DELETE FROM role_permissions WHERE role_id = ?', [$roleIds[$role]]);
    foreach ($perms as $slug) {
        if (!isset($permissionIds[$slug])) {
            echo "WARN missing permission slug: {$slug}\n";
            continue;
        }
        Database::query(
            'INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?, ?)',
            [$roleIds[$role], $permissionIds[$slug]]
        );
    }
    echo strtoupper($role) . ' => ' . count($perms) . " permissions\n";
}

echo "Role permission sync complete.\n";
