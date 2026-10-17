<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AssessmentController;
use App\Http\Controllers\Api\AssessmentAttemptController;
use App\Models\AssessmentAttempt;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::get('/assessments', [AssessmentController::class, 'index']);


Route::middleware('auth:sanctum')->post(
    '/assessments/{assessment:slug}/attempts',
    [AssessmentAttemptController::class, 'store']
);


Route::middleware('auth:sanctum')->put(
    '/attempts/{attempt}/answers',
    [AssessmentAttemptController::class, 'storeAnswers']
);


Route::middleware('auth:sanctum')->post(
    '/attempts/{attempt}/complete',
    [AssessmentAttemptController::class, 'complete']
);


Route::middleware('auth:sanctum')->group(function () {
    Route::get(
        '/my/attempts',
        [AssessmentAttemptController::class, 'history']
    );

    Route::get(
        '/attempts/{attempt}/result',
        [AssessmentAttemptController::class, 'showResult']
    );
});
