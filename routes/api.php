<?php

use App\Http\Controllers\ScanController;
use Illuminate\Support\Facades\Route;

Route::post('/scan', [ScanController::class, 'api'])
    ->middleware('throttle:api-scan')
    ->name('api.scan');
