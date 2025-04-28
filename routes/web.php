<?php

use App\Http\Controllers\PreseedController;

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [PreseedController::class, 'index'])->name('preseed.index');
Route::get('/preseed', [PreseedController::class, 'index'])->name('preseed.index');
Route::post('/preseed', [PreseedController::class, 'store'])->name('preseed.store');
Route::post('/preseed/preview', [PreseedController::class, 'preview'])->name('preseed.preview');
Route::get('/preseed/{waarde}', [PreseedController::class, 'show'])->name('preseed.show');

Route::get('dashboard', function () { return Inertia::render('Dashboard'); })->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
