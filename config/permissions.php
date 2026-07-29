<?php

/**
 * Permission map: module.action
 * Used by seeders and can() helper documentation.
 */
return [
    'dashboard' => ['view'],
    'patients' => ['view', 'add', 'edit', 'delete', 'print', 'export'],
    'appointments' => ['view', 'add', 'edit', 'delete', 'status_change', 'print', 'export'],
    'calendar' => ['view', 'add', 'edit'],
    'queue' => ['view', 'status_change'],
    'follow_ups' => ['view', 'add', 'edit', 'delete', 'status_change'],
    'visits' => ['view', 'add', 'edit', 'delete'],
    'treatments' => ['view', 'add', 'edit', 'delete', 'approve', 'status_change'],
    'treatment_sessions' => ['view', 'add', 'edit', 'delete'],
    'prescriptions' => ['view', 'add', 'edit', 'delete', 'print'],
    'doctors' => ['view', 'add', 'edit', 'delete'],
    'reference_doctors' => ['view', 'add', 'edit', 'delete'],
    'treatment_masters' => ['view', 'add', 'edit', 'delete'],
    'medicine_masters' => ['view', 'add', 'edit', 'delete'],
    'appointment_statuses' => ['view', 'add', 'edit', 'delete'],
    'billing' => ['view', 'add', 'edit', 'delete', 'print', 'approve'],
    'payments' => ['view', 'add', 'edit', 'delete', 'print', 'approve'],
    'outstanding' => ['view', 'export'],
    'inventory' => ['view', 'add', 'edit', 'delete', 'approve'],
    'suppliers' => ['view', 'add', 'edit', 'delete'],
    'purchases' => ['view', 'add', 'edit', 'delete', 'approve'],
    'reports' => ['view', 'export', 'print'],
    'users' => ['view', 'add', 'edit', 'delete'],
    'roles' => ['view', 'add', 'edit', 'delete'],
    'approvals' => ['view', 'approve'],
    'audit_logs' => ['view'],
    'settings' => ['view', 'edit'],
];
