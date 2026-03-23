<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Modules\Admin\Http\Controllers\DashboardController;
use Modules\Admin\Http\Controllers\MemberController;
use Modules\Admin\Http\Controllers\LoanController;
use Modules\Admin\Http\Controllers\LoanTypeController;
use Modules\Admin\Http\Controllers\DeductionController;
use Modules\Admin\Http\Controllers\ContributionController;
use Modules\Admin\Http\Controllers\AnnouncementController;
use Modules\Admin\Http\Controllers\ActivityLogController;
use Modules\Admin\Http\Controllers\AdminProfileController;



Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Members
    Route::get('/members', [MemberController::class, 'index'])->name('members.index');
    Route::get('/members/create', [MemberController::class, 'create'])->name('members.create');
    Route::post('/members', [MemberController::class, 'store'])->name('members.store');
    Route::get('/members/template', [MemberController::class, 'downloadTemplate'])->name('members.template');
    Route::post('/members/import', [MemberController::class, 'import'])->name('members.import');
    Route::get('/members/{user}', [MemberController::class, 'show'])->name('members.show');
    Route::patch('/members/{user}/toggle-active', [MemberController::class, 'toggleActive'])->name('members.toggle-active');


    // Loan Types
    Route::get('/loan-types', [LoanTypeController::class, 'index'])->name('loan-types.index');
    Route::post('/loan-types', [LoanTypeController::class, 'store'])->name('loan-types.store');
    Route::patch('/loan-types/{loanType}', [LoanTypeController::class, 'update'])->name('loan-types.update');
    Route::patch('/loan-types/{loanType}/toggle-active', [LoanTypeController::class, 'toggleActive'])->name('loan-types.toggle-active');

    // Loans
    Route::get('/loans', [LoanController::class, 'index'])->name('loans.index');
    Route::get('/loans/create', [LoanController::class, 'create'])->name('loans.create');
    Route::post('/loans', [LoanController::class, 'store'])->name('loans.store');
    Route::get('/loans/{loan}/edit', [LoanController::class, 'edit'])->name('loans.edit');
    Route::patch('/loans/{loan}', [LoanController::class, 'update'])->name('loans.update');
    Route::patch('/loans/{loan}/complete', [LoanController::class, 'markComplete'])->name('loans.complete');
    Route::patch('/loans/{loan}/cancel', [LoanController::class, 'cancel'])->name('loans.cancel');

    // Placeholders

    Route::get('/deductions', [DeductionController::class, 'index'])->name('deductions.index');
    Route::patch('/deductions/{deduction}/approve', [DeductionController::class, 'approve'])->name('deductions.approve');
    Route::patch('/deductions/{deduction}/reject', [DeductionController::class, 'reject'])->name('deductions.reject');



    Route::get('/contributions', [ContributionController::class, 'index'])->name('contributions.index');
    Route::patch('/contributions/{contribution}/approve', [ContributionController::class, 'approve'])->name('contributions.approve');
    Route::patch('/contributions/{contribution}/reject', [ContributionController::class, 'reject'])->name('contributions.reject');


    Route::get('/announcements', [AnnouncementController::class, 'index'])->name('announcements.index');
    Route::post('/announcements', [AnnouncementController::class, 'store'])->name('announcements.store');
    Route::patch('/announcements/{announcement}', [AnnouncementController::class, 'update'])->name('announcements.update');
    Route::delete('/announcements/{announcement}', [AnnouncementController::class, 'destroy'])->name('announcements.destroy');
    Route::post('/announcements/{announcement}/comment', [AnnouncementController::class, 'comment'])->name('announcements.comment');
    Route::delete('/announcements/comments/{comment}', [AnnouncementController::class, 'deleteComment'])->name('announcements.comments.destroy');

    Route::get('/activity', [ActivityLogController::class, 'index'])->name('activity.index');

    Route::get('/profile', [AdminProfileController::class, 'index'])->name('profile.index');
    Route::patch('/profile', [AdminProfileController::class, 'updateProfile'])->name('profile.update');
    Route::post('/profile/photo', [AdminProfileController::class, 'updatePhoto'])->name('profile.photo');
    Route::patch('/profile/password', [AdminProfileController::class, 'updatePassword'])->name('profile.password');
});
