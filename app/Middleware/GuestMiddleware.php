<?php

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\App;

class GuestMiddleware
{
    public function handle(Request $request): void
    {
        if (Auth::check()) {
            Response::redirect(App::url('dashboard'));
        }
    }
}
