<?php

use App\Http\Controllers\AnalysisJobController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExceptionController;
use App\Http\Controllers\InspectionTaskController;
use App\Http\Controllers\SampleController;
use App\Http\Controllers\SampleResultController;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard/summary', [DashboardController::class, 'summary']);

Route::get('/inspection-tasks', [InspectionTaskController::class, 'index']);
Route::get('/inspection-tasks/{id}', [InspectionTaskController::class, 'show']);
Route::post('/inspection-tasks/{id}/start', [InspectionTaskController::class, 'start']);
Route::post('/inspection-tasks/{id}/submit', [InspectionTaskController::class, 'submit']);

Route::get('/samples', [SampleController::class, 'index']);
Route::post('/samples', [SampleController::class, 'store']);
Route::get('/samples/{id}', [SampleController::class, 'show']);

Route::get('/samples/{id}/results', [SampleResultController::class, 'index']);
Route::post('/samples/{id}/results', [SampleResultController::class, 'store']);

Route::get('/exceptions', [ExceptionController::class, 'index']);
Route::post('/exceptions', [ExceptionController::class, 'store']);
Route::post('/exceptions/{id}/resolve', [ExceptionController::class, 'resolve']);

Route::get('/analysis-jobs', [AnalysisJobController::class, 'index']);
Route::post('/analysis-jobs', [AnalysisJobController::class, 'store']);
Route::get('/analysis-jobs/{id}', [AnalysisJobController::class, 'show']);
Route::post('/analysis-jobs/{id}/start', [AnalysisJobController::class, 'start']);
Route::post('/analysis-jobs/{id}/succeed', [AnalysisJobController::class, 'succeed']);
Route::post('/analysis-jobs/{id}/fail', [AnalysisJobController::class, 'fail']);
Route::post('/analysis-jobs/{id}/cancel', [AnalysisJobController::class, 'cancel']);
Route::post('/analysis-jobs/{id}/retry', [AnalysisJobController::class, 'retry']);
