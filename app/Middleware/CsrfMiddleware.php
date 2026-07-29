<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

class CsrfMiddleware
{
    public function handle(Request $request): void
    {
        if (in_array($request->method(), ['POST', 'PUT', 'DELETE', 'PATCH'], true)) {
            $token = $request->input('_token')
                ?? $request->header('X-CSRF-TOKEN')
                ?? $request->header('X-XSRF-TOKEN');

            if (!$token || !hash_equals(Session::csrfToken(), (string) $token)) {
                if ($request->isAjax()) {
                    Response::error('Invalid security token. Please refresh and try again.', null, 419);
                }
                http_response_code(419);
                echo 'CSRF token mismatch.';
                exit;
            }
        }
    }
}
