<?php

use App\Http\Controllers\App\ReportController;

Route::group(['prefix' => 'reports'], function () {
    Route::get('/advisor', [ReportController::class, 'getAdvisorReports']);
    Route::get('/advisors', [ReportController::class, 'getAdvisors']);
});
