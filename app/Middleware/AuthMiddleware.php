<?php

namespace App\Middleware;

use App\Controllers\LabMasterController;
use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\App;

class AuthMiddleware
{
    public function handle(Request $request): void
    {
        if (!Auth::check()) {
            if ($request->isAjax()) {
                Response::error('Unauthenticated.', null, 401);
            }
            Response::redirect(App::url('login'));
        }

        // Ensure Lab Master table + admin menu permissions exist
        LabMasterController::ensureReady();
    }
}
