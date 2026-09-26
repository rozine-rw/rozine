<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AuditorEngagementController;
use App\Http\Controllers\Api\V1\AuditorJobsController;
use App\Http\Controllers\Api\V1\AuditorProcedureController;
use App\Http\Controllers\Api\V1\AuditorProfileController;
use App\Http\Controllers\Api\V1\BusinessApplicationController;
use App\Http\Controllers\Api\V1\BusinessAuditReportController;
use App\Http\Controllers\Api\V1\IdentityController;
use App\Http\Controllers\Api\V1\IdentityManagementController;
use App\Http\Controllers\Api\V1\RoleBookmarkController;
use App\Http\Controllers\Api\V1\StaffAccessController;
use App\Http\Controllers\AuditDisputeController;
use App\Http\Controllers\AuditOperationsController;
use App\Http\Controllers\AuditSealVerificationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('v1/audit-seals/{report}', AuditSealVerificationController::class)->whereUlid('report')
    ->middleware(['throttle:60,1', 'cache.headers:no_store'])->name('api.v1.audit.seals.verify');

Route::middleware(['auth:sanctum', 'throttle:60,1', 'cache.headers:private;no_store'])->prefix('v1/business')->name('api.v1.business.audit-reports.')->group(function (): void {
    Route::get('audit-report-operations/{request_id}', [BusinessAuditReportController::class, 'operation'])->whereUuid('request_id')->name('operations.show');
    Route::get('{business}/audit-reports/{report}', [BusinessAuditReportController::class, 'show'])->whereUlid(['business', 'report'])->name('show');
    Route::post('{business}/audit-reports/{report}/cosign', [BusinessAuditReportController::class, 'cosign'])->whereUlid(['business', 'report'])->name('cosign');
    Route::post('{business}/audit-reports/{report}/dispute', [AuditDisputeController::class, 'dispute'])->whereUlid(['business', 'report'])->name('dispute');
    Route::get('{business}/audit-reports/{report}/dispute/proofs/{proof}', [AuditDisputeController::class, 'proof'])->whereUlid(['business', 'report', 'proof'])->defaults('review_role', 'business')->name('disputes.proofs.show');
});

Route::get('v1/business', [BusinessApplicationController::class, 'index'])
    ->middleware(['auth:sanctum', 'throttle:60,1', 'cache.headers:private;no_store'])->name('api.v1.business.index');

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('v1/identity', IdentityController::class)
    ->middleware(['auth:sanctum', 'throttle:60,1'])
    ->name('api.v1.identity.show');

Route::middleware(['auth:sanctum', 'throttle:60,1', 'cache.headers:private;no_store'])->prefix('v1/business')->name('api.v1.business.applications.')->group(function (): void {
    Route::get('application-operations/{request_id}', [BusinessApplicationController::class, 'operation'])->whereUuid('request_id')->name('operations.show');
    Route::post('{business}/applications', [BusinessApplicationController::class, 'create'])->whereUlid('business')->name('create');
    Route::get('{business}/applications/{application}', [BusinessApplicationController::class, 'show'])->whereUlid(['business', 'application'])->name('show');
    foreach (['save', 'evaluate', 'submit'] as $command) {
        Route::post('{business}/applications/{application}/'.$command, [BusinessApplicationController::class, $command])->whereUlid(['business', 'application'])->name($command);
    }
});

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
    Route::get('dispute-operations/{request_id}', [AuditDisputeController::class, 'operation'])->whereUuid('request_id')->name('disputes.operations.show');
    Route::get('{assignment}/reports/{report}/dispute', [AuditDisputeController::class, 'show'])->whereUlid(['assignment', 'report'])->name('disputes.show');
    Route::get('{assignment}/reports/{report}/dispute/proofs/{proof}', [AuditDisputeController::class, 'proof'])->whereUlid(['assignment', 'report', 'proof'])->defaults('review_role', 'staff')->name('disputes.proofs.show');
    foreach (['escalate', 'resolve'] as $decision) {
        Route::post('{assignment}/reports/{report}/dispute/'.$decision, [AuditDisputeController::class, 'resolve'])->whereUlid(['assignment', 'report'])->defaults('decision_kind', $decision)->name('disputes.'.$decision);
    }
    Route::get('{assignment}', [AuditOperationsController::class, 'show'])->where('assignment', '[0-9a-z]{26}')->name('show');
    foreach (['redispatch', 'close'] as $decision) {
        Route::post('{assignment}/'.$decision, [AuditOperationsController::class, 'resolve'])->where('assignment', '[0-9a-z]{26}')->defaults('decision', $decision)->name($decision);
    }
});

Route::middleware(['auth:sanctum', 'throttle:60,1'])->prefix('v1/auditor')->name('api.v1.auditor.')->group(function (): void {
    Route::post('jobs/{assignment}/report', [AuditorProcedureController::class, 'start'])->where('assignment', '[0-9a-z]{26}')->middleware('cache.headers:private;no_store')->name('reports.start');
    Route::get('report-operations/{request_id}', [AuditorProcedureController::class, 'operation'])->whereUuid('request_id')->middleware('cache.headers:private;no_store')->name('reports.operations.show');
    Route::post('reports/{report}/dispute/uphold', [AuditDisputeController::class, 'uphold'])->whereUlid('report')->middleware('cache.headers:private;no_store')->name('reports.disputes.uphold');
    Route::get('reports/{report}/dispute/proofs/{proof}', [AuditDisputeController::class, 'proof'])->whereUlid(['report', 'proof'])->middleware('cache.headers:private;no_store')->defaults('review_role', 'auditor')->name('reports.disputes.proofs.show');
    Route::get('reports/{report}', [AuditorProcedureController::class, 'show'])->where('report', '[0-9a-z]{26}')->middleware('cache.headers:private;no_store')->name('reports.show');
    Route::get('reports/{report}/statements/{document}', [AuditorProcedureController::class, 'statement'])->where(['report' => '[0-9a-z]{26}', 'document' => '[0-9a-z]{26}'])->middleware('cache.headers:private;no_store')->name('reports.statements.show');
    Route::get('reports/{report}/ledgers/{document}', [AuditorProcedureController::class, 'ledger'])->where(['report' => '[0-9a-z]{26}', 'document' => '[0-9a-z]{26}'])->middleware('cache.headers:private;no_store')->name('reports.ledgers.show');
    Route::post('reports/{report}/steps', [AuditorProcedureController::class, 'save'])->where('report', '[0-9a-z]{26}')->middleware('cache.headers:private;no_store')->name('reports.save');
    Route::post('reports/{report}/step-up', [AuditorProcedureController::class, 'stepUp'])->where('report', '[0-9a-z]{26}')->middleware(['throttle:audit-step-up', 'cache.headers:private;no_store'])->name('reports.step-up');
    Route::post('reports/{report}/seal', [AuditorProcedureController::class, 'seal'])->where('report', '[0-9a-z]{26}')->middleware('cache.headers:private;no_store')->name('reports.seal');
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
    Route::get('profile', [AuditorProfileController::class, 'show'])->name('profile');
    Route::post('accreditation', [AuditorProfileController::class, 'submit'])->name('accreditation.submit');
    Route::post('accreditation/renewal', [AuditorProfileController::class, 'renew'])->name('accreditation.renew');
    Route::post('accreditation/withdrawal', [AuditorProfileController::class, 'withdraw'])->name('accreditation.withdraw');
    Route::get('accreditation/certificates/{certificate}', [AuditorProfileController::class, 'certificate'])
        ->where('certificate', '[0-9a-z]{26}')->name('accreditation.certificates.show');
    Route::post('availability', [AuditorProfileController::class, 'availability'])->name('availability.update');
    Route::get('operations/{request_id}', [AuditorProfileController::class, 'operation'])->whereUuid('request_id')->name('operations.show');
});
