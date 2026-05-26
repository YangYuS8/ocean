<?php

use App\Http\Controllers\AnalysisJobController;
use App\Http\Controllers\AuditEventController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExceptionController;
use App\Http\Controllers\GovernanceController;
use App\Http\Controllers\InspectionTaskController;
use App\Http\Controllers\SampleController;
use App\Http\Controllers\SampleResultController;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard/summary', [DashboardController::class, 'summary']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::get('/auth/me', [AuthController::class, 'me'])->middleware('ocean.auth:token');
Route::post('/auth/logout', [AuthController::class, 'logout'])->middleware('ocean.auth:token');
Route::get('/governance/me', [GovernanceController::class, 'me']);
Route::get('/governance/roles', [GovernanceController::class, 'roles']);
Route::get('/audit-events', [AuditEventController::class, 'index']);

Route::get('/inspection-tasks', [InspectionTaskController::class, 'index']);
Route::get('/inspection-tasks/{id}', [InspectionTaskController::class, 'show']);
Route::post('/inspection-tasks/{id}/start', [InspectionTaskController::class, 'start'])->middleware('ocean.permission:inspection_task.start,token');
Route::post('/inspection-tasks/{id}/submit', [InspectionTaskController::class, 'submit'])->middleware('ocean.permission:inspection_task.submit,token');

Route::get('/samples', [SampleController::class, 'index']);
Route::post('/samples', [SampleController::class, 'store'])->middleware('ocean.permission:sample.create,token');
Route::get('/samples/{id}', [SampleController::class, 'show']);
Route::post('/samples/{id}/main-image', [SampleController::class, 'storeMainImage'])->middleware('ocean.permission:sample.image.upload,token');
Route::get('/samples/{id}/main-image/content', [SampleController::class, 'showMainImageContent']);
Route::get('/samples/{id}/image-suggestion', [SampleController::class, 'showImageSuggestion']);

Route::get('/samples/{id}/results', [SampleResultController::class, 'index']);
Route::post('/samples/{id}/results', [SampleResultController::class, 'store'])->middleware('ocean.permission:sample_result.create,token');

Route::get('/exceptions', [ExceptionController::class, 'index']);
Route::post('/exceptions', [ExceptionController::class, 'store'])->middleware('ocean.permission:exception.create,token');
Route::post('/exceptions/{id}/resolve', [ExceptionController::class, 'resolve'])->middleware('ocean.permission:exception.resolve,token');

Route::get('/analysis-jobs', [AnalysisJobController::class, 'index']);
Route::post('/analysis-jobs', [AnalysisJobController::class, 'store'])->middleware('ocean.permission:analysis_job.create,token');
Route::get('/analysis-jobs/{id}', [AnalysisJobController::class, 'show']);
Route::post('/analysis-jobs/{id}/start', [AnalysisJobController::class, 'start'])->middleware('ocean.permission:analysis_job.start,worker');
Route::post('/analysis-jobs/{id}/succeed', [AnalysisJobController::class, 'succeed'])->middleware('ocean.permission:analysis_job.succeed,worker');
Route::post('/analysis-jobs/{id}/fail', [AnalysisJobController::class, 'fail'])->middleware('ocean.permission:analysis_job.fail,worker');
Route::post('/analysis-jobs/{id}/cancel', [AnalysisJobController::class, 'cancel'])->middleware('ocean.permission:analysis_job.cancel,token');
Route::post('/analysis-jobs/{id}/retry', [AnalysisJobController::class, 'retry'])->middleware('ocean.permission:analysis_job.retry,token');
