<?php

use App\Http\Controllers\Api\V1\ApplicationController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CandidateController;
use App\Http\Controllers\Api\V1\CandidateSkillController;
use App\Http\Controllers\Api\V1\CompanyController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\InterviewController;
use App\Http\Controllers\Api\V1\InterviewFeedbackController;
use App\Http\Controllers\Api\V1\OfferController;
use App\Http\Controllers\Api\V1\PositionController;
use App\Http\Controllers\Api\V1\PositionMatchingController;
use App\Http\Controllers\Api\V1\PositionRequirementController;
use App\Http\Controllers\Api\V1\SkillController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('api.version')->group(function () {
    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me', [AuthController::class, 'me']);

        Route::get('dashboard', [DashboardController::class, 'stats']);
        Route::apiResource('companies', CompanyController::class)->only(['index', 'store', 'show']);
        Route::apiResource('positions', PositionController::class)->only(['index', 'store', 'show']);
        Route::apiResource('candidates', CandidateController::class)->only(['index', 'store', 'show']);
        Route::apiResource('applications', ApplicationController::class)->only(['index', 'store', 'show']);
        Route::apiResource('skills', SkillController::class)->only(['index', 'store']);
        Route::apiResource('interviews', InterviewController::class)->only(['index', 'store', 'show', 'update']);
        Route::get('applications/{application}/timeline', [ApplicationController::class, 'timeline']);
        Route::apiResource('offers', OfferController::class)->only(['index', 'store', 'show']);
        Route::patch('offers/{offer}/status', [OfferController::class, 'updateStatus']);

        Route::patch('applications/{application}/stage', [ApplicationController::class, 'updateStage']);

        Route::post('positions/{position}/requirements', [PositionRequirementController::class, 'sync']);
        Route::post('candidates/{candidate}/skills', [CandidateSkillController::class, 'sync']);

        Route::get('positions/{position}/recommendations', [PositionMatchingController::class, 'recommendations']);
        Route::get('positions/{position}/candidates/{candidate}/match', [PositionMatchingController::class, 'compare']);

        Route::post('interviews/{interview}/feedback', [InterviewFeedbackController::class, 'store']);
    });
});
