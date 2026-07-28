<?php

use App\Http\Controllers\PulseController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PulseController::class, 'index'])->name('home');

Route::middleware('throttle:10,1')->group(function () {
    Route::post('pulse/statement', [PulseController::class, 'storeStatement'])->name('pulse.statement.store');
    Route::post('pulse/investor', [PulseController::class, 'storeInvestor'])->name('pulse.investor.store');
    Route::post('pulse/business', [PulseController::class, 'storeBusiness'])->name('pulse.business.store');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
