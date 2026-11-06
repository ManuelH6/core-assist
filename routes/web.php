<?php

use App\Modules\Analysis\Http\Controllers\AnalysisController;
use App\Modules\FinancialCollection\Http\Controllers\CollectionController;
use App\Modules\ProjectManagement\Http\Controllers\CollaboratorController;
use App\Modules\ProjectManagement\Http\Controllers\MissionController;
use App\Modules\ProjectManagement\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Outils Financiers
Route::resource('collections', CollectionController::class);
Route::resource('analyses', AnalysisController::class)->only(['index', 'store', 'show']);

// Gestion de Projet & Staffing Cabinet
Route::resource('missions', MissionController::class);
Route::resource('collaborators', CollaboratorController::class);
Route::post('tasks', [TaskController::class, 'store'])->name('tasks.store');
Route::post('tasks/{task}/status', [TaskController::class, 'updateStatus'])->name('tasks.status');
Route::delete('tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');

// Routes API JSON
Route::prefix('api')->name('api.')->group(function () {
    Route::apiResource('collections', CollectionController::class);
    Route::apiResource('analyses', AnalysisController::class)->only(['index', 'store', 'show']);
    Route::get('collections/{collection}/analyses', [AnalysisController::class, 'byCollection'])->name('collections.analyses');

    Route::apiResource('missions', MissionController::class);
    Route::apiResource('collaborators', CollaboratorController::class);
    Route::post('tasks', [TaskController::class, 'store'])->name('tasks.store');
    Route::post('tasks/{task}/status', [TaskController::class, 'updateStatus'])->name('tasks.status');
    Route::delete('tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');
});
