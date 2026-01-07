<?php

use App\Http\Controllers\Api\ItemController;
use App\Http\Controllers\Api\ProjectController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['auth:sanctum'])->group(function () {
    Route::apiResource('projects', ProjectController::class);
    Route::apiResource('items', ItemController::class);

    Route::post('items/{item}/reaudit', [ItemController::class, 'reaudit']);
    Route::post('items/{item}/mark-reviewed', [ItemController::class, 'markReviewed']);
});
