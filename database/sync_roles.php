<?php
/**
 * Sync role permissions + demo users only (no sample clinical data overwrite).
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
    'receptionist' => [
        'dashboard.view',
        'calendar.view', 'calendar.add', 'calendar.edit',
        'appointments.view', 'appointments.add', 'appointments.edit', 'appointments.status_change', 'appointments.print',
        'queue.view', 'queue.status_change',
        'patients.view', 'patients.add', 'patients.edit', 'patients.print',
        'follow_ups.view', 'follow_ups.add', 'follow_ups.edit', 'follow_ups.status_change',
        'visits.view', 'visits.add',
        'prescriptions.view', 'prescriptions.print',
        'doctors.view', 'reference_doctors.view', 'treatment_masters.view',
        'billing.view', 'billing.add', 'billing.edit', 'billing.print',
        'payments.view', 'payments.add', 'payments.print',
        'outstanding.view', 'outstanding.export',
    ],
    'doctor' => [
        'dashboard.view',
        'calendar.view', 'calendar.add', 'calendar.edit',
        'appointments.view', 'appointments.add', 'appointments.edit', 'appointments.status_change', 'appointments.print',
        'queue.view', 'queue.status_change',
        'patients.view', 'patients.print',
        'visits.view', 'visits.add', 'visits.edit',
        'treatments.view', 'treatments.add', 'treatments.edit', 'treatments.status_change',
        'treatment_sessions.view', 'treatment_sessions.add', 'treatment_sessions.edit',
        'prescriptions.view', 'prescriptions.add', 'prescriptions.edit', 'prescriptions.print',
        'follow_ups.view', 'follow_ups.add', 'follow_ups.edit', 'follow_ups.status_change',
        'medicine_masters.view', 'treatment_masters.view',
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

$demoUsers = [
    ['accounts', 'Accounts Desk', 'accounts@rootsdental.local', 'Accounts@123', 'accounts'],
    ['inventory', 'Inventory Staff', 'inventory@rootsdental.local', 'Inventory@123', 'inventory'],
];
foreach ($demoUsers as [$username, $name, $email, $password, $role]) {
    $user = Database::fetch('SELECT id FROM users WHERE username = ?', [$username]);
    if (!$user) {
        $id = Database::insert('users', [
            'name' => $name,
            'username' => $username,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_BCRYPT),
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    } else {
        $id = (int) $user['id'];
        Database::update('users', [
            'name' => $name,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_BCRYPT),
            'is_active' => 1,
            'updated_at' => $now,
        ], 'id = :_id', ['_id' => $id]);
    }
    Database::query('INSERT IGNORE INTO user_roles (user_id, role_id) VALUES (?, ?)', [$id, $roleIds[$role]]);
    echo "User ready: {$username} / {$password}\n";
}

echo "Role permission sync complete.\n";
