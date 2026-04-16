<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use App\Services\ReportingService;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/generate-report', function (ReportingService $reportingService) {
    $result = $reportingService->run();
    
    return response()->json($result);
});

Route::get('/clear-cache', function () {
    Artisan::call('optimize:clear');
    Artisan::call('view:clear');
    Artisan::call('config:clear');
    Artisan::call('route:clear');
    Artisan::call('cache:clear');
    
    return response()->json([
        'status' => 'success',
        'message' => 'All caches cleared successfully.'
    ]);
});
