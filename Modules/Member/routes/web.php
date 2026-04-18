<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Modules\Member\Http\Controllers\DashboardController;
use Modules\Member\Http\Controllers\DeductionController;
use Modules\Member\Http\Controllers\ContributionController;
use Modules\Member\Http\Controllers\AnnouncementController;
use Modules\Member\Http\Controllers\ProfileController;
use Modules\Member\Http\Controllers\LoanController;
use Modules\Member\Http\Controllers\MemberController;
use Modules\Member\Http\Controllers\LoanApplicationController;
use Modules\Member\Http\Controllers\SavingsController;
use Modules\Member\Http\Controllers\MessageController;

Route::middleware(['auth', 'role:member'])->prefix('member')->name('member.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/deductions', [DeductionController::class, 'index'])->name('deductions.index');
    Route::post('/deductions/confirm', [DeductionController::class, 'confirm'])->name('deductions.confirm');


    Route::get('/loans', [LoanController::class, 'index'])->name('loans.index');
    Route::get('/loans/create', [LoanController::class, 'create'])->name('loans.create');
    Route::post('/loans/store', [LoanController::class, 'store'])->name('loans.store');
    Route::get('/loans/history', [LoanController::class, 'history'])->name('loans.history');

    // Loan Applications
    Route::get('/loan-applications', [LoanApplicationController::class, 'index'])->name('loan-applications.index');
    Route::get('/loan-applications/create', [LoanApplicationController::class, 'create'])->name('loan-applications.create');
    Route::post('/loan-applications', [LoanApplicationController::class, 'store'])->name('loan-applications.store');
    Route::get('/loan-applications/{loanApplication}', [LoanApplicationController::class, 'show'])->name('loan-applications.show');

    Route::get('/payments', [ContributionController::class, 'index'])->name('payments.index');
    Route::post('/payments', [ContributionController::class, 'store'])->name('payments.store');

    // Savings
    Route::get('/savings', [SavingsController::class, 'index'])->name('savings.index');
    Route::get('/savings/create', [SavingsController::class, 'create'])->name('savings.create');
    Route::post('/savings', [SavingsController::class, 'store'])->name('savings.store');
    Route::get('/savings/{savingsContribution}', [SavingsController::class, 'show'])->name('savings.show');


    Route::get('/announcements', [AnnouncementController::class, 'index'])->name('announcements.index');
    Route::post('/announcements/{announcement}/comment', [AnnouncementController::class, 'comment'])->name('announcements.comment');
    Route::delete('/announcements/comments/{comment}', [AnnouncementController::class, 'deleteComment'])->name('announcements.comments.destroy');


    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
    Route::patch('/profile', [ProfileController::class, 'updateProfile'])->name('profile.update');
    Route::post('/profile/photo', [ProfileController::class, 'updatePhoto'])->name('profile.photo');
    Route::patch('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // Messages
    Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
    Route::get('/messages/create', [MessageController::class, 'create'])->name('messages.create');
    Route::post('/messages', [MessageController::class, 'send'])->name('messages.send');
    Route::get('/messages/{message}', [MessageController::class, 'show'])->name('messages.show');
    Route::post('/messages/{message}/reply', [MessageController::class, 'reply'])->name('messages.reply');
    Route::delete('/messages/{message}', [MessageController::class, 'destroy'])->name('messages.destroy');
    Route::patch('/messages/{message}/read', [MessageController::class, 'markAsRead'])->name('messages.read');
    Route::get('/messages/unread/count', [MessageController::class, 'unreadCount'])->name('messages.unread-count');

});
