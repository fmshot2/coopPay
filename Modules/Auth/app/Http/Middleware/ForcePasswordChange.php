<?php

namespace Modules\Auth\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ForcePasswordChange
{
    public function handle(Request $request, Closure $next)
    {
        if (
            Auth::check() &&
            Auth::user()->must_change_password &&
            !$request->routeIs('password.change') &&
            !$request->routeIs('password.change.post') &&
            !$request->routeIs('logout')
        ) {
            return redirect()->route('password.change');
        }

        return $next($request);
    }
}
