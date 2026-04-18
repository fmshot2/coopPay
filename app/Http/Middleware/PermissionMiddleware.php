<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, string $permission)
    {
        if (!Auth::check() || !Auth::user()->hasPermissionTo($permission)) {
            abort(403, 'Unauthorized');
        }

        return $next($request);
    }
}
