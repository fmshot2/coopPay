<?php

use Illuminate\Support\Facades\Route;
use Modules\Division\Http\Controllers\DivisionController;

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
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
