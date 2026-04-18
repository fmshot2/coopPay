<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\AuthController;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])
        ->name('login');

    Route::post('/login', [AuthController::class, 'login'])
        ->name('login.post');
});

Route::middleware('auth')->group(function () {
    Route::get('/change-password', [AuthController::class, 'showChangePassword'])
        ->name('password.change');

    Route::post('/change-password', [AuthController::class, 'changePassword'])
        ->name('password.change.post');

    Route::get('/profile/complete', [AuthController::class, 'showProfileComplete'])
        ->name('profile.complete');

    Route::post('/profile/complete', [AuthController::class, 'profileComplete'])
        ->name('profile.complete.post');

    Route::post('/logout', [AuthController::class, 'logout'])
        ->name('logout');
});
