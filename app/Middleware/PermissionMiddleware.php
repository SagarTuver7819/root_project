<?php

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;

class PermissionMiddleware
{
    private static ?string $required = null;

    public static function setRequired(?string $permission): void
    {
        self::$required = $permission;
    }

    public function handle(Request $request): void
    {
        $permission = self::$required;
        if (!$permission) {
            return;
        }

        if (!Auth::can($permission)) {
            if ($request->isAjax()) {
                Response::error('You do not have permission to perform this action.', null, 403);
            }
            http_response_code(403);
            echo '403 Forbidden';
            exit;
        }
    }
}
