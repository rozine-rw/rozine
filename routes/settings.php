<?php

declare(strict_types=1);

use App\Http\Controllers\Settings\AccountClosureController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SecurityController;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Routing\RedirectController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('settings/close-account', [AccountClosureController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('account-closure.store');

    Route::get('settings/security', [SecurityController::class, 'edit'])
        ->middleware(RequirePassword::class)
        ->name('security.edit');

    // The password form lives on Security; a bookmarked or typed settings/password opens it there.
    Route::get('settings/password', RedirectController::class)
        ->defaults('destination', '/settings/security')
        ->defaults('status', 302);

    Route::put('settings/password', [SecurityController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::put('settings/login-alerts', [SecurityController::class, 'updateLoginAlerts'])
        ->middleware('throttle:changes')
        ->name('login-alerts.update');

    Route::inertia('settings/appearance', 'settings/appearance')->name('appearance.edit');
});

Route::get('.well-known/passkey-endpoints', function () {
    return response()->json([
        'enroll' => route('security.edit'),
        'manage' => route('security.edit'),
    ]);
})->name('well-known.passkeys');
