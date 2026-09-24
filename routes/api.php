<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AuditorJobsController;
use App\Http\Controllers\Api\V1\AuditorProfileController;
use App\Http\Controllers\Api\V1\IdentityController;
use App\Http\Controllers\Api\V1\IdentityManagementController;
use App\Http\Controllers\Api\V1\RoleBookmarkController;
use App\Http\Controllers\Api\V1\StaffAccessController;
use App\Http\Controllers\AuditOperationsController;
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

Route::middleware(['auth:sanctum', 'throttle:60,1', 'cache.headers:private;no_store'])->prefix('v1/staff/audit-assignments')->name('api.v1.staff.audit.')->group(function (): void {
    Route::get('operations/{request_id}', [AuditOperationsController::class, 'operation'])->whereUuid('request_id')->name('operations.show');
    Route::get('{assignment}', [AuditOperationsController::class, 'show'])->where('assignment', '[0-9a-z]{26}')->name('show');
    foreach (['redispatch', 'close'] as $decision) {
        Route::post('{assignment}/'.$decision, [AuditOperationsController::class, 'resolve'])->where('assignment', '[0-9a-z]{26}')->defaults('decision', $decision)->name($decision);
    }
});

Route::middleware(['auth:sanctum', 'throttle:60,1'])->prefix('v1/auditor')->name('api.v1.auditor.')->group(function (): void {
    Route::get('jobs', [AuditorJobsController::class, 'index'])->middleware('cache.headers:private;no_store')->name('jobs.index');
    Route::get('jobs/{assignment}', [AuditorJobsController::class, 'show'])->where('assignment', '[0-9a-z]{26}')->middleware('cache.headers:private;no_store')->name('jobs.show');
    Route::get('conflicts', [AuditorJobsController::class, 'conflicts'])->middleware('cache.headers:private;no_store')->name('conflicts.index');
    Route::get('jobs/{assignment}/conflict', [AuditorJobsController::class, 'conflict'])->where('assignment', '[0-9a-z]{26}')->middleware('cache.headers:private;no_store')->name('conflicts.show');
    foreach (['accept', 'decline', 'conflict'] as $decision) {
        Route::post('jobs/{assignment}/'.$decision, [AuditorJobsController::class, 'respond'])->where('assignment', '[0-9a-z]{26}')->defaults('decision', $decision)->name('jobs.'.$decision);
    }
    Route::get('assignment-operations/{request_id}', [AuditorJobsController::class, 'operation'])->whereUuid('request_id')->middleware('cache.headers:private;no_store')->name('jobs.operations.show');
    Route::get('profile', [AuditorProfileController::class, 'show'])->name('profile');
    Route::post('accreditation', [AuditorProfileController::class, 'submit'])->name('accreditation.submit');
    Route::post('accreditation/renewal', [AuditorProfileController::class, 'renew'])->name('accreditation.renew');
    Route::post('accreditation/withdrawal', [AuditorProfileController::class, 'withdraw'])->name('accreditation.withdraw');
    Route::get('accreditation/certificates/{certificate}', [AuditorProfileController::class, 'certificate'])
        ->where('certificate', '[0-9a-z]{26}')->name('accreditation.certificates.show');
    Route::post('availability', [AuditorProfileController::class, 'availability'])->name('availability.update');
    Route::get('operations/{request_id}', [AuditorProfileController::class, 'operation'])->whereUuid('request_id')->name('operations.show');
});
