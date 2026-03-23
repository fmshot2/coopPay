<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Modules\Member\Http\Controllers\DashboardController;
use Modules\Member\Http\Controllers\DeductionController;
use Modules\Member\Http\Controllers\ContributionController;
use Modules\Member\Http\Controllers\AnnouncementController;
use Modules\Member\Http\Controllers\ProfileController;
use Modules\Member\Http\Controllers\LoanController;

Route::middleware(['auth', 'role:member'])->prefix('member')->name('member.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/deductions', [DeductionController::class, 'index'])->name('deductions.index');
    Route::post('/deductions/confirm', [DeductionController::class, 'confirm'])->name('deductions.confirm');

    Route::get('/loan', [LoanController::class, 'index'])->name('loan.index');


    Route::get('/payments', [ContributionController::class, 'index'])->name('payments.index');
    Route::post('/payments', [ContributionController::class, 'store'])->name('payments.store');


    Route::get('/announcements', [AnnouncementController::class, 'index'])->name('announcements.index');
    Route::post('/announcements/{announcement}/comment', [AnnouncementController::class, 'comment'])->name('announcements.comment');
    Route::delete('/announcements/comments/{comment}', [AnnouncementController::class, 'deleteComment'])->name('announcements.comments.destroy');


    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
    Route::patch('/profile', [ProfileController::class, 'updateProfile'])->name('profile.update');
    Route::post('/profile/photo', [ProfileController::class, 'updatePhoto'])->name('profile.photo');
    Route::patch('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

});
