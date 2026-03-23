<?php

use Illuminate\Support\Facades\Route;
use Modules\Contribution\Http\Controllers\ContributionController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('contributions', ContributionController::class)->names('contribution');
});
