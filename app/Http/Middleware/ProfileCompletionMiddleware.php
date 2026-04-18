<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ProfileCompletionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip profile completion check for login and logout routes
        if ($request->routeIs('login*') || $request->routeIs('logout')) {
            return $next($request);
        }

        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Skip profile completion check for password change route
        if ($request->routeIs('password.change*')) {
            return $next($request);
        }

        // Check if profile is incomplete (email and phone are required)
        if (empty($user->email) || empty($user->phone)) {
            // Allow access to profile completion routes
            if ($request->routeIs('profile.complete*')) {
                return $next($request);
            }

            return redirect()->route('profile.complete');
        }

        return $next($request);
    }
}
