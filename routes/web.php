<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/generate-report', function () {
    Artisan::call('report:generate');
    $output = Artisan::output();
    
    return response()->json([
        'status' => 'success',
        'message' => 'Report generation triggered.',
        'output' => $output
    ]);
});
