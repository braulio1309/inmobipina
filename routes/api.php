<?php

use Illuminate\Http\Request;
use App\Http\Controllers\DietController;
use App\Http\Controllers\ReportController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

/*Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});*/

Route::middleware('auth:api')->post('/diets/generate', [DietController::class, 'generate']);

// Reports API routes
Route::middleware('auth:api')->group(function () {
    Route::get('/reports/advisor-metrics', [ReportController::class, 'getAdvisorMetrics']);
    Route::get('/reports/advisors', [ReportController::class, 'getAdvisors']);
});
