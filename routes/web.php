<?php

declare(strict_types=1);

use App\Http\Controllers\AuditOperationsController;
use App\Http\Controllers\AuditorEngagementController;
use App\Http\Controllers\AuditorJobsController;
use App\Http\Controllers\AuditorProcedureController;
use App\Http\Controllers\AuditorProfileController;
use App\Http\Controllers\BusinessApplicationController;
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
    Route::get('business', RoleHomeController::class)->middleware(['throttle:60,1', 'cache.headers:private;no_store'])->name('business.home');
    Route::get('auditor', RoleHomeController::class)->name('auditor.home');
    Route::get('admin', StaffHomeController::class)->name('admin.home');
    Route::get('auditor/profile', [AuditorProfileController::class, 'show'])->name('auditor.profile');
});

Route::middleware(['auth', 'throttle:60,1'])->prefix('auditor')->name('auditor.')->group(function (): void {
    Route::post('jobs/{assignment}/report', [AuditorProcedureController::class, 'start'])->where('assignment', '[0-9a-z]{26}')->middleware('cache.headers:private;no_store')->name('reports.start');
    Route::get('report-operations/{request_id}', [AuditorProcedureController::class, 'operation'])->whereUuid('request_id')->middleware('cache.headers:private;no_store')->name('reports.operations.show');
    Route::get('reports/{report}', [AuditorProcedureController::class, 'show'])->where('report', '[0-9a-z]{26}')->middleware('cache.headers:private;no_store')->name('reports.show');
    Route::get('reports/{report}/statements/{document}', [AuditorProcedureController::class, 'statement'])->where(['report' => '[0-9a-z]{26}', 'document' => '[0-9a-z]{26}'])->middleware('cache.headers:private;no_store')->name('reports.statements.show');
    Route::get('reports/{report}/ledgers/{document}', [AuditorProcedureController::class, 'ledger'])->where(['report' => '[0-9a-z]{26}', 'document' => '[0-9a-z]{26}'])->middleware('cache.headers:private;no_store')->name('reports.ledgers.show');
    Route::post('reports/{report}/steps', [AuditorProcedureController::class, 'save'])->where('report', '[0-9a-z]{26}')->middleware('cache.headers:private;no_store')->name('reports.save');
    Route::post('reports/{report}/request-changes', [AuditorProcedureController::class, 'requestChanges'])->where('report', '[0-9a-z]{26}')->middleware('cache.headers:private;no_store')->name('reports.request-changes');
    Route::post('reports/{report}/reject', [AuditorProcedureController::class, 'reject'])->where('report', '[0-9a-z]{26}')->middleware('cache.headers:private;no_store')->name('reports.reject');
    Route::post('reports/{report}/amend', [AuditorProcedureController::class, 'amend'])->where('report', '[0-9a-z]{26}')->middleware('cache.headers:private;no_store')->name('reports.amend');
    Route::get('engagement', [AuditorEngagementController::class, 'show'])->middleware('cache.headers:private;no_store')->name('engagement.show');
    Route::post('engagement/accept', [AuditorEngagementController::class, 'accept'])->middleware('cache.headers:private;no_store')->name('engagement.accept');
    Route::get('engagement/operations/{request_id}', [AuditorEngagementController::class, 'operation'])->whereUuid('request_id')->middleware('cache.headers:private;no_store')->name('engagement.operations.show');
    Route::get('jobs', [AuditorJobsController::class, 'index'])->middleware('cache.headers:private;no_store')->name('jobs.index');
    Route::get('jobs/{assignment}', [AuditorJobsController::class, 'show'])->where('assignment', '[0-9a-z]{26}')->middleware('cache.headers:private;no_store')->name('jobs.show');
    Route::get('conflicts', [AuditorJobsController::class, 'conflicts'])->middleware('cache.headers:private;no_store')->name('conflicts.index');
    Route::get('jobs/{assignment}/conflict', [AuditorJobsController::class, 'conflict'])->where('assignment', '[0-9a-z]{26}')->middleware('cache.headers:private;no_store')->name('conflicts.show');
    foreach (['accept', 'decline', 'conflict'] as $decision) {
        Route::post('jobs/{assignment}/'.$decision, [AuditorJobsController::class, 'respond'])->where('assignment', '[0-9a-z]{26}')->defaults('decision', $decision)->name('jobs.'.$decision);
    }
    Route::get('assignment-operations/{request_id}', [AuditorJobsController::class, 'operation'])->whereUuid('request_id')->middleware('cache.headers:private;no_store')->name('jobs.operations.show');
    Route::post('accreditation', [AuditorProfileController::class, 'submit'])->name('accreditation.submit');
    Route::post('accreditation/renewal', [AuditorProfileController::class, 'renew'])->name('accreditation.renew');
    Route::post('accreditation/withdrawal', [AuditorProfileController::class, 'withdraw'])->name('accreditation.withdraw');
    Route::get('accreditation/certificates/{certificate}', [AuditorProfileController::class, 'certificate'])
        ->where('certificate', '[0-9a-z]{26}')->name('accreditation.certificates.show');
    Route::post('availability', [AuditorProfileController::class, 'availability'])->name('availability.update');
    Route::get('operations/{request_id}', [AuditorProfileController::class, 'operation'])->whereUuid('request_id')->name('operations.show');
});

Route::middleware(['auth', 'throttle:60,1', 'cache.headers:private;no_store'])->prefix('business')->name('business.applications.')->group(function (): void {
    Route::get('application-operations/{request_id}', [BusinessApplicationController::class, 'operation'])->whereUuid('request_id')->name('operations.show');
    Route::post('{business}/applications', [BusinessApplicationController::class, 'create'])->whereUlid('business')->name('create');
    Route::get('{business}/applications/{application}', [BusinessApplicationController::class, 'show'])->whereUlid(['business', 'application'])->name('show');
    foreach (['save', 'evaluate', 'submit'] as $command) {
        Route::post('{business}/applications/{application}/'.$command, [BusinessApplicationController::class, $command])->whereUlid(['business', 'application'])->name($command);
    }
});

Route::middleware(['auth', 'throttle:60,1', 'cache.headers:private;no_store'])->prefix('admin/audit-assignments')->name('staff.audit.')->group(function (): void {
    Route::get('operations/{request_id}', [AuditOperationsController::class, 'operation'])->whereUuid('request_id')->name('operations.show');
    Route::get('{assignment}', [AuditOperationsController::class, 'show'])->where('assignment', '[0-9a-z]{26}')->name('show');
    foreach (['redispatch', 'close'] as $decision) {
        Route::post('{assignment}/'.$decision, [AuditOperationsController::class, 'resolve'])->where('assignment', '[0-9a-z]{26}')->defaults('decision', $decision)->name($decision);
    }
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
