<?php

use Illuminate\Support\Facades\Route;
use Modules\Admin\Http\Controllers\AdminController;
use Modules\Admin\Http\Controllers\MemberController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('admins', AdminController::class)->names('admin');

    Route::middleware(['role:admin', 'permission:manage-members'])
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {
            Route::get('/members', [MemberController::class, 'index'])->name('members.index');
            Route::get('/members/conflicted', [MemberController::class, 'membersConflictIndex'])->name('members.conflicted');
            Route::post('/members', [MemberController::class, 'store'])->name('members.store');
            Route::get('/members/create', [MemberController::class, 'create'])->name('members.create');
            Route::get('/members/template', [MemberController::class, 'downloadTemplate'])->name('members.template');
            Route::post('/members/import/csv', [MemberController::class, 'importCsv'])->name('members.import.csv');
            Route::post('/members/import', [MemberController::class, 'import'])->name('members.import.process');
            Route::get('/years/{year}/months', [MemberController::class, 'months'])->name('years.months');
            Route::get('/members/{user}', [MemberController::class, 'show'])->name('members.show');
            Route::get('/members/{user}/edit', [MemberController::class, 'edit'])->name('members.edit');
            Route::patch('/members/{user}', [MemberController::class, 'update'])->name('members.update');
            Route::patch('/members/{user}/toggle-active', [MemberController::class, 'toggleActive'])->name('members.toggle-active');
            Route::patch('/members/{user}/reset-password', [MemberController::class, 'resetPassword'])->name('members.reset-password');
            Route::patch('/members/{user}/update-max-loan', [MemberController::class, 'updateMaxLoan'])->name('members.update-max-loan');
            Route::get('/members/{user}/assignees-search', [MemberController::class, 'searchAssignees'])->name('members.assignees-search');
            Route::patch('/members/{user}/reassign', [MemberController::class, 'reassign'])->name('members.reassign');
        });
});
