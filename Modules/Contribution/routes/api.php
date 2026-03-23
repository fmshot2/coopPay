<?php

use Illuminate\Support\Facades\Route;
use Modules\Contribution\Http\Controllers\ContributionController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('contributions', ContributionController::class)->names('contribution');
});
