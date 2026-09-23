<?php

use App\Http\Controllers\ScanController;
use App\Http\Controllers\TextExtractController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
})->name('home');

Route::get('/extract', [TextExtractController::class, 'index'])->name('extract.index');
Route::post('/extract', [TextExtractController::class, 'store'])->name('extract.store');
Route::post('/extract/sample', [TextExtractController::class, 'sample'])->name('extract.sample');

Route::get('/scan', [ScanController::class, 'index'])->name('scan.index');
Route::post('/scan', [ScanController::class, 'store'])->name('scan.store');
