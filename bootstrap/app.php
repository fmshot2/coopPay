<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->redirectTo(
            guests: '/login',
            users: function (Request $request) {
                if ($request->user()?->hasRole('admin')) {
                    return route('admin.members.index');
                }
                return route('admin.dashboard');
            }
        );
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Modules\Auth\Http\Middleware\ForcePasswordChange::class,
            \App\Http\Middleware\ProfileCompletionMiddleware::class,
        ]);
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'permission' => \App\Http\Middleware\PermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
