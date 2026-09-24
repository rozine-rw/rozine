<?php

declare(strict_types=1);

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IdentityManagementController;
use App\Http\Controllers\PulseController;
use App\Http\Controllers\SiteController;
use Illuminate\Support\Facades\Route;

Route::get('/', [SiteController::class, 'index'])->name('home');

Route::get('pulse', [PulseController::class, 'index'])->name('pulse');

Route::middleware('throttle:60,1')->group(function () {
    Route::post('pulse/investor/preview', [PulseController::class, 'previewInvestor'])->name('pulse.investor.preview');
    Route::post('pulse/business/preview', [PulseController::class, 'previewBusiness'])->name('pulse.business.preview');
});

Route::middleware('throttle:10,1')->group(function () {
    Route::post('pulse/investor', [PulseController::class, 'storeInvestor'])->name('pulse.investor.store');
    Route::post('pulse/business', [PulseController::class, 'storeBusiness'])->name('pulse.business.store');
});

Route::middleware('throttle:10,1')->group(function () {
    Route::post('investor', [SiteController::class, 'storeInvestor'])->name('site.investor.store');
    Route::post('business', [SiteController::class, 'storeBusiness'])->name('site.business.store');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
});

Route::middleware(['auth', 'throttle:60,1'])->prefix('identity')->name('identity.')->group(function (): void {
    Route::post('people/resolve', [IdentityManagementController::class, 'resolvePerson'])->name('people.resolve');
    Route::post('memberships', [IdentityManagementController::class, 'membership'])->name('memberships.update');
    Route::post('active-role', [IdentityManagementController::class, 'selectRole'])->name('active-role.store');
    Route::get('roles/{role}', [IdentityManagementController::class, 'role'])->name('roles.show');
});

require __DIR__.'/settings.php';

require __DIR__.'/preview.php';
