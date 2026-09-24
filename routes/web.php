<?php

declare(strict_types=1);

use App\Http\Controllers\AuditorProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IdentityManagementController;
use App\Http\Controllers\PulseController;
use App\Http\Controllers\RoleBookmarkController;
use App\Http\Controllers\RoleHomeController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\StaffHomeController;
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
    Route::get('investor', RoleHomeController::class)->name('investor.home');
    Route::get('business', RoleHomeController::class)->name('business.home');
    Route::get('auditor', RoleHomeController::class)->name('auditor.home');
    Route::get('admin', StaffHomeController::class)->name('admin.home');
    Route::get('auditor/profile', [AuditorProfileController::class, 'show'])->name('auditor.profile');
});

Route::middleware(['auth', 'throttle:60,1'])->prefix('auditor')->name('auditor.')->group(function (): void {
    Route::post('accreditation', [AuditorProfileController::class, 'submit'])->name('accreditation.submit');
    Route::post('accreditation/renewal', [AuditorProfileController::class, 'renew'])->name('accreditation.renew');
    Route::post('accreditation/withdrawal', [AuditorProfileController::class, 'withdraw'])->name('accreditation.withdraw');
    Route::get('accreditation/certificates/{certificate}', [AuditorProfileController::class, 'certificate'])
        ->where('certificate', '[0-9a-z]{26}')->name('accreditation.certificates.show');
    Route::post('availability', [AuditorProfileController::class, 'availability'])->name('availability.update');
    Route::get('operations/{request_id}', [AuditorProfileController::class, 'operation'])->whereUuid('request_id')->name('operations.show');
});

Route::middleware(['auth', 'throttle:60,1'])->prefix('identity')->name('identity.')->group(function (): void {
    Route::post('people/resolve', [IdentityManagementController::class, 'resolvePerson'])->name('people.resolve');
    Route::post('memberships', [IdentityManagementController::class, 'membership'])->name('memberships.update');
    Route::post('active-role', [IdentityManagementController::class, 'selectRole'])->name('active-role.store');
    Route::get('roles/{role}', [IdentityManagementController::class, 'role'])->name('roles.show');
    Route::get('bookmarks/{role}', [RoleBookmarkController::class, 'show'])->name('bookmarks.show');
    Route::post('bookmarks', [RoleBookmarkController::class, 'store'])->name('bookmarks.store');
    Route::get('roles/{role}/resume', [RoleBookmarkController::class, 'resume'])->name('roles.resume');
});

require __DIR__.'/settings.php';

require __DIR__.'/preview.php';
