<?php

use App\Http\Controllers\ScanController;
use Illuminate\Support\Facades\Route;

Route::post('/scan', [ScanController::class, 'api'])->name('api.scan');
