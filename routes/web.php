<?php

use App\Http\Controllers\AdminSkillController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompareController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\LetterController;
use App\Http\Controllers\ScanController;
use App\Http\Controllers\TextExtractController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
})->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'createLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1')
        ->name('auth.login');
    Route::get('/register', [AuthController::class, 'createRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('auth.register.store');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('auth.logout');

Route::get('/extract', [TextExtractController::class, 'index'])->name('extract.index');
Route::post('/extract', [TextExtractController::class, 'store'])->name('extract.store');
Route::post('/extract/sample', [TextExtractController::class, 'sample'])->name('extract.sample');

Route::get('/scan', [ScanController::class, 'index'])->name('scan.index');
Route::post('/scan', [ScanController::class, 'store'])->name('scan.store');

Route::get('/compare', [CompareController::class, 'index'])->name('compare.index');
Route::post('/compare', [CompareController::class, 'store'])->name('compare.store');

Route::get('/letter', [LetterController::class, 'index'])->name('letter.index');
Route::post('/letter', [LetterController::class, 'store'])->name('letter.store');

Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/skills', [AdminSkillController::class, 'index'])->name('skills.index');
    Route::post('/skills', [AdminSkillController::class, 'store'])->name('skills.store');
    Route::put('/skills/{skill}', [AdminSkillController::class, 'update'])->name('skills.update');
    Route::delete('/skills/{skill}', [AdminSkillController::class, 'destroy'])->name('skills.destroy');
});

Route::get('/history', [HistoryController::class, 'index'])->name('history.index');
Route::get('/history/{scan}', [HistoryController::class, 'show'])->name('history.show');
Route::get('/history/{scan}/export', [HistoryController::class, 'exportCsv'])->name('history.export');
Route::delete('/history/{scan}', [HistoryController::class, 'destroy'])->name('history.destroy');
Route::get('/share/{scan}', [HistoryController::class, 'share'])
    ->middleware('signed')
    ->name('history.share');
