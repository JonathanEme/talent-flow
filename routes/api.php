<?php

use App\Http\Controllers\Api\V1\CompanyController;
use App\Http\Controllers\Api\V1\PositionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::apiResource('companies', CompanyController::class)->only(['index', 'store', 'show']);
    Route::apiResource('positions', PositionController::class)->only(['index', 'store', 'show']);
});