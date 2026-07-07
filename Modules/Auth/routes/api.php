<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\AuthController;

// Public
Route::prefix('v1')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->name('api.login');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLinkEmail'])->name('api.password.email');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('api.password.update');
});

// Authenticated (token required)
Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::get('/me', [AuthController::class, 'me'])->name('api.me');
    Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout');
    Route::post('/change-password', [AuthController::class, 'changePassword'])->name('api.password.change');
    Route::post('/profile/complete', [AuthController::class, 'profileComplete'])->name('api.profile.complete');
});
