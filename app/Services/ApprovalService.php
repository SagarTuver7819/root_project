<?php

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;

class ApprovalService
{
    public static function request(string $module, int $recordId, string $actionType, ?string $reason = null, mixed $payload = null): int
    {
        return Database::insert('approval_requests', [
            'module' => $module,
            'record_id' => $recordId,
            'action_type' => $actionType,
            'requested_by' => Auth::id() ?: 0,
            'request_date' => date('Y-m-d H:i:s'),
            'reason' => $reason,
            'status' => 'pending',
            'payload' => $payload === null ? null : json_encode($payload),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
