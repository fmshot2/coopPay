<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Modules\Admin\Http\Controllers\DashboardController;
use Modules\Admin\Http\Controllers\MemberController;
use Modules\Admin\Http\Controllers\LoanPlanController;
use Modules\Admin\Http\Controllers\LoanTypeController;
use Modules\Admin\Http\Controllers\DeductionController;
use Modules\Admin\Http\Controllers\ContributionController;
use Modules\Admin\Http\Controllers\AnnouncementController;
use Modules\Admin\Http\Controllers\ActivityLogController;
use Modules\Admin\Http\Controllers\AdminProfileController;
use Modules\Admin\Http\Controllers\RoleController;
use Modules\Admin\Http\Controllers\PermissionController;
use Modules\Admin\Http\Controllers\LoanApplicationController;
use Modules\Admin\Http\Controllers\MessageController;
use Modules\Admin\Http\Controllers\SettingsController;
use Modules\Division\Http\Controllers\DivisionController;



Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Members
    Route::middleware('permission:manage-members')->group(function () {
        Route::get('/members', [MemberController::class, 'index'])->name('members.index');
        Route::get('/members/create', [MemberController::class, 'create'])->name('members.create');
        Route::post('/members', [MemberController::class, 'store'])->name('members.store');
        Route::get('/members/template', [MemberController::class, 'downloadTemplate'])->name('members.template');
        Route::post('/members/import', [MemberController::class, 'import'])->name('members.import');
        Route::get('/members/{user}', [MemberController::class, 'show'])->name('members.show');
        Route::get('/members/{user}/edit', [MemberController::class, 'edit'])->name('members.edit');
        Route::patch('/members/{user}', [MemberController::class, 'update'])->name('members.update');
        Route::patch('/members/{user}/toggle-active', [MemberController::class, 'toggleActive'])->name('members.toggle-active');
        Route::patch('/members/{user}/reset-password', [MemberController::class, 'resetPassword'])->name('members.reset-password');
        Route::patch('/members/{user}/update-max-loan', [MemberController::class, 'updateMaxLoan'])->name('members.update-max-loan');
    });

    // Roles
    Route::middleware('permission:manage-roles')->group(function () {
        Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
        Route::get('/roles/create', [RoleController::class, 'create'])->name('roles.create');
        Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
        Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])->name('roles.edit');
        Route::patch('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
        Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');
    });

    // Permissions
    Route::middleware('permission:manage-permissions')->group(function () {
        Route::get('/permissions', [PermissionController::class, 'index'])->name('permissions.index');
        Route::get('/permissions/create', [PermissionController::class, 'create'])->name('permissions.create');
        Route::post('/permissions', [PermissionController::class, 'store'])->name('permissions.store');
        Route::get('/permissions/{permission}/edit', [PermissionController::class, 'edit'])->name('permissions.edit');
        Route::patch('/permissions/{permission}', [PermissionController::class, 'update'])->name('permissions.update');
        Route::delete('/permissions/{permission}', [PermissionController::class, 'destroy'])->name('permissions.destroy');
    });

    // Loan Types
    Route::middleware('permission:manage-loan-types')->group(function () {
        Route::get('/loan-types', [LoanTypeController::class, 'index'])->name('loan-types.index');
        Route::post('/loan-types', [LoanTypeController::class, 'store'])->name('loan-types.store');
        Route::patch('/loan-types/{loanType}', [LoanTypeController::class, 'update'])->name('loan-types.update');
        Route::patch('/loan-types/{loanType}/toggle-active', [LoanTypeController::class, 'toggleActive'])->name('loan-types.toggle-active');
    });

    // Loans
    Route::middleware('permission:manage-loans')->group(function () {
        Route::get('/loans', [LoanPlanController::class, 'index'])->name('loans.index');
        Route::get('/loans/applications', [LoanPlanController::class, 'loan_applications'])->name('loans.applications');
        Route::get('/loans/create', [LoanPlanController::class, 'create'])->name('loans.create');
        Route::post('/loans', [LoanPlanController::class, 'store'])->name('loans.store');
        Route::get('/loans/{loan}/edit', [LoanPlanController::class, 'edit'])->name('loans.edit');
        Route::patch('/loans/{loan}', [LoanPlanController::class, 'update'])->name('loans.update');
        Route::patch('/loans/{loan}/complete', [LoanPlanController::class, 'markComplete'])->name('loans.complete');
        Route::patch('/loans/{loan}/cancel', [LoanPlanController::class, 'cancel'])->name('loans.cancel');
    });

    // Loan Applications
    Route::middleware('permission:manage-loans')->group(function () {
        Route::get('/loan-applications', [LoanApplicationController::class, 'index'])->name('loan-applications.index');
        Route::get('/loan-applications/{loanApplication}', [LoanApplicationController::class, 'show'])->name('loan-applications.show');
        Route::patch('/loan-applications/{loanApplication}/approve', [LoanApplicationController::class, 'approve'])->name('loan-applications.approve');
        Route::patch('/loan-applications/{loanApplication}/reject', [LoanApplicationController::class, 'reject'])->name('loan-applications.reject');
    });

    // Deductions
    Route::middleware('permission:manage-deductions')->group(function () {
        Route::get('/deductions', [DeductionController::class, 'index'])->name('deductions.index');
        Route::patch('/deductions/{deduction}/approve', [DeductionController::class, 'approve'])->name('deductions.approve');
        Route::patch('/deductions/{deduction}/reject', [DeductionController::class, 'reject'])->name('deductions.reject');
    });

    // Contributions
    Route::middleware('permission:manage-contributions')->group(function () {
        Route::get('/contributions', [ContributionController::class, 'index'])->name('contributions.index');
        Route::patch('/contributions/{contribution}/approve', [ContributionController::class, 'approve'])->name('contributions.approve');
        Route::patch('/contributions/{contribution}/reject', [ContributionController::class, 'reject'])->name('contributions.reject');
        Route::patch('/contributions/{contribution}/approve-savings', [ContributionController::class, 'approveSavings'])->name('contributions.approve-savings');
        Route::patch('/contributions/{contribution}/reject-savings', [ContributionController::class, 'rejectSavings'])->name('contributions.reject-savings');
    });

    // Announcements
    Route::middleware('permission:manage-announcements')->group(function () {
        Route::get('/announcements', [AnnouncementController::class, 'index'])->name('announcements.index');
        Route::post('/announcements', [AnnouncementController::class, 'store'])->name('announcements.store');
        Route::patch('/announcements/{announcement}', [AnnouncementController::class, 'update'])->name('announcements.update');
        Route::delete('/announcements/{announcement}', [AnnouncementController::class, 'destroy'])->name('announcements.destroy');
        Route::post('/announcements/{announcement}/comment', [AnnouncementController::class, 'comment'])->name('announcements.comment');
        Route::delete('/announcements/comments/{comment}', [AnnouncementController::class, 'deleteComment'])->name('announcements.comments.destroy');
    });

    // Activity Log
    Route::middleware('permission:view-activity-log')->group(function () {
        Route::get('/activity', [ActivityLogController::class, 'index'])->name('activity.index');
    });

    // Profile
    Route::get('/profile', [AdminProfileController::class, 'index'])->name('profile.index');
    Route::patch('/profile', [AdminProfileController::class, 'updateProfile'])->name('profile.update');
    Route::post('/profile/photo', [AdminProfileController::class, 'updatePhoto'])->name('profile.photo');
    Route::patch('/profile/password', [AdminProfileController::class, 'updatePassword'])->name('profile.password');

    // Messages
    Route::middleware('permission:manage-messages')->group(function () {
        Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
        Route::get('/messages/create', [MessageController::class, 'create'])->name('messages.create');
        Route::post('/messages', [MessageController::class, 'send'])->name('messages.send');
        Route::get('/messages/{message}', [MessageController::class, 'show'])->name('messages.show');
        Route::post('/messages/{message}/reply', [MessageController::class, 'reply'])->name('messages.reply');
        Route::post('/messages/{message}/reply-email', [MessageController::class, 'replyViaEmail'])->name('messages.reply-email');
        Route::delete('/messages/{message}', [MessageController::class, 'destroy'])->name('messages.destroy');
        Route::patch('/messages/{message}/read', [MessageController::class, 'markAsRead'])->name('messages.read');
        Route::get('/messages/unread/count', [MessageController::class, 'unreadCount'])->name('messages.unread-count');
    });

    // Settings
    Route::middleware('permission:manage-settings')->group(function () {
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::patch('/settings', [SettingsController::class, 'update'])->name('settings.update');
    });

    // Divisions
    Route::middleware('permission:manage-divisions')->group(function () {
        Route::get('/divisions', [DivisionController::class, 'index'])->name('divisions.index');
        Route::get('/divisions/create', [DivisionController::class, 'create'])->name('divisions.create');
        Route::post('/divisions', [DivisionController::class, 'store'])->name('divisions.store');
        Route::get('/divisions/{division}', [DivisionController::class, 'show'])->name('divisions.show');
        Route::get('/divisions/{division}/edit', [DivisionController::class, 'edit'])->name('divisions.edit');
        Route::patch('/divisions/{division}', [DivisionController::class, 'update'])->name('divisions.update');
        Route::delete('/divisions/{division}', [DivisionController::class, 'destroy'])->name('divisions.destroy');
        Route::patch('/divisions/{division}/toggle-active', [DivisionController::class, 'toggleActive'])->name('divisions.toggle-active');
    });
});
