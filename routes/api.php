<?php

use App\Http\Controllers\Api\AssessmentAttemptController;
use App\Http\Controllers\Api\AssessmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ExternalIntegrationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| مسیرهای احراز هویت یکپارچه (SSO) برای Next.js
|--------------------------------------------------------------------------
*/
Route::post('/auth/sso', [AuthController::class, 'sso']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});

/*
|--------------------------------------------------------------------------
| مسیرهای عمومی و آزمون‌ها برای Next.js
|--------------------------------------------------------------------------
*/
Route::get('/assessments', [AssessmentController::class, 'index']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post(
        '/assessments/{assessment:slug}/attempts',
        [AssessmentAttemptController::class, 'store']
    );

    Route::put(
        '/attempts/{attempt}/answers',
        [AssessmentAttemptController::class, 'storeAnswers']
    );

    Route::post(
        '/attempts/{attempt}/complete',
        [AssessmentAttemptController::class, 'complete']
    );

    Route::get(
        '/my/attempts',
        [AssessmentAttemptController::class, 'history']
    );

    Route::get(
        '/attempts/{attempt}/result',
        [AssessmentAttemptController::class, 'showResult']
    );
});

/*
|--------------------------------------------------------------------------
| مسیرهای یک‌طرفه سرور-به-سرور برای سامانه اصلی شرکت
|--------------------------------------------------------------------------
*/
Route::get(
    '/external/users/{externalUserId}/results',
    [ExternalIntegrationController::class, 'getUserResults']
);
