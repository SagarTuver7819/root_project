<?php

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;

class AuditService
{
    public static function log(
        string $module,
        string $action,
        ?int $recordId = null,
        mixed $oldValues = null,
        mixed $newValues = null
    ): void {
        try {
            Database::insert('audit_logs', [
                'user_id' => Auth::id(),
                'module' => $module,
                'record_id' => $recordId,
                'action' => $action,
                'old_values' => $oldValues !== null ? json_encode($oldValues) : null,
                'new_values' => $newValues !== null ? json_encode($newValues) : null,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            // Never break main flow due to audit failure
            error_log('Audit log failed: ' . $e->getMessage());
        }
    }
}
