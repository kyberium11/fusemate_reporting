<?php

use Illuminate\Support\Facades\Route;
use App\Services\ReportingService;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/generate-report', function (ReportingService $reportingService) {
    $result = $reportingService->run();
    
    return response()->json($result);
});
