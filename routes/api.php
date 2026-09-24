<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\IdentityController;
use App\Http\Controllers\Api\V1\IdentityManagementController;
use App\Http\Controllers\Api\V1\RoleBookmarkController;
use App\Http\Controllers\Api\V1\StaffAccessController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('v1/identity', IdentityController::class)
    ->middleware(['auth:sanctum', 'throttle:60,1'])
    ->name('api.v1.identity.show');

Route::get('v1/staff-access', StaffAccessController::class)
    ->middleware(['auth:sanctum', 'throttle:60,1'])
    ->name('api.v1.staff-access.show');

Route::middleware(['auth:sanctum', 'throttle:60,1'])->prefix('v1/identity')->name('api.v1.identity.')->group(function (): void {
    Route::post('people/resolve', [IdentityManagementController::class, 'resolvePerson'])->name('people.resolve');
    Route::post('memberships', [IdentityManagementController::class, 'membership'])->name('memberships.update');
    Route::post('active-role', [IdentityManagementController::class, 'selectRole'])->name('active-role.store');
    Route::get('roles/{role}', [IdentityManagementController::class, 'role'])->name('roles.show');
    Route::get('bookmarks/{role}', [RoleBookmarkController::class, 'show'])->name('bookmarks.show');
    Route::post('bookmarks', [RoleBookmarkController::class, 'store'])->name('bookmarks.store');
});
