<?php

use App\Http\Controllers\Api\V1\ApplicationController;
use App\Http\Controllers\Api\V1\CandidateController;
use App\Http\Controllers\Api\V1\CandidateSkillController;
use App\Http\Controllers\Api\V1\CompanyController;
use App\Http\Controllers\Api\V1\PositionController;
use App\Http\Controllers\Api\V1\PositionMatchingController;
use App\Http\Controllers\Api\V1\PositionRequirementController;
use App\Http\Controllers\Api\V1\SkillController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::apiResource('companies', CompanyController::class)->only(['index', 'store', 'show']);
    Route::apiResource('positions', PositionController::class)->only(['index', 'store', 'show']);
    Route::apiResource('candidates', CandidateController::class)->only(['index', 'store', 'show']);
    Route::apiResource('applications', ApplicationController::class)->only(['index', 'store', 'show']);
    Route::apiResource('skills', SkillController::class)->only(['index', 'store']);

    Route::patch('applications/{application}/stage', [ApplicationController::class, 'updateStage']);
    
    // Skills & Requirements
    Route::post('positions/{position}/requirements', [PositionRequirementController::class, 'sync']);
    Route::post('candidates/{candidate}/skills', [CandidateSkillController::class, 'sync']);

    // Matching Engine
    Route::get('positions/{position}/recommendations', [PositionMatchingController::class, 'recommendations']);
    Route::get('positions/{position}/candidates/{candidate}/match', [PositionMatchingController::class, 'compare']);
});