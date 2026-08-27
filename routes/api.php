<?php

use App\Http\Controllers\Api\V1\ApplicationController;
use App\Http\Controllers\Api\V1\CandidateController;
use App\Http\Controllers\Api\V1\CompanyController;
use App\Http\Controllers\Api\V1\PositionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::apiResource('companies', CompanyController::class)->only(['index', 'store', 'show']);
    Route::apiResource('positions', PositionController::class)->only(['index', 'store', 'show']);
    Route::apiResource('candidates', CandidateController::class)->only(['index', 'store', 'show']);
    Route::apiResource('applications', ApplicationController::class)->only(['index', 'store', 'show']);
    
    // Endpoint para mover de etapa en el flujo
    Route::patch('applications/{application}/stage', [ApplicationController::class, 'updateStage']);
});