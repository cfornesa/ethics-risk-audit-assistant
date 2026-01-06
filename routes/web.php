<?php

use App\Http\Controllers\ItemController;
use App\Http\Controllers\ProjectController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('projects.index');
});

Route::resource('projects', ProjectController::class);
Route::get('projects/{project}/export/{format?}', [ProjectController::class, 'export'])
    ->name('projects.export');

Route::resource('items', ItemController::class);
Route::post('items/{item}/reaudit', [ItemController::class, 'reaudit'])
    ->name('items.reaudit');
Route::post('items/{item}/mark-reviewed', [ItemController::class, 'markReviewed'])
    ->name('items.mark-reviewed');
