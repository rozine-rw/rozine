<?php

declare(strict_types=1);

use App\Http\Controllers\AuditDisputeController;
use App\Http\Controllers\AuditOperationsController;
use App\Http\Controllers\AuditorEngagementController;
use App\Http\Controllers\AuditorHomeController;
use App\Http\Controllers\AuditorJobsController;
use App\Http\Controllers\AuditorPortfolioController;
use App\Http\Controllers\AuditorProcedureController;
use App\Http\Controllers\AuditorProfileController;
use App\Http\Controllers\AuditSealVerificationController;
use App\Http\Controllers\BusinessApplicationController;
use App\Http\Controllers\BusinessAuditReportController;
use App\Http\Controllers\BusinessHomeController;
use App\Http\Controllers\BusinessPublicationController;
use App\Http\Controllers\BusinessRepaymentController;
use App\Http\Controllers\BusinessWalletController;
use App\Http\Controllers\ChangeFeedController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmailVerificationCodeController;
use App\Http\Controllers\IdentityManagementController;
use App\Http\Controllers\InvestorDealsController;
use App\Http\Controllers\InvestorMarketController;
use App\Http\Controllers\InvestorPortfolioController;
use App\Http\Controllers\InvestorPrimaryController;
use App\Http\Controllers\InvestorProfileController;
use App\Http\Controllers\InvestorVerificationController;
use App\Http\Controllers\InvestorWalletController;
use App\Http\Controllers\PulseController;
use App\Http\Controllers\RoleBookmarkController;
use App\Http\Controllers\RoleHomeController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\StaffActivityController;
use App\Http\Controllers\StaffApplicationReleaseController;
use App\Http\Controllers\StaffAuditorDirectoryController;
use App\Http\Controllers\StaffBusinessDirectoryController;
use App\Http\Controllers\StaffDashboardController;
use App\Http\Controllers\StaffDirectoryController;
use App\Http\Controllers\StaffDisbursementController;
use App\Http\Controllers\StaffHomeController;
use App\Http\Controllers\StaffInvestorDirectoryController;
use App\Http\Controllers\StaffInvestorVerificationController;
use App\Http\Controllers\StaffSectionController;
use App\Http\Controllers\StaffStagingMailTesterController;
use App\Http\Middleware\EnsureStagingMailTesterAccess;
use Illuminate\Support\Facades\Route;

Route::get('/', [SiteController::class, 'index'])->name('home');

Route::get('audit-seals/{report}', AuditSealVerificationController::class)->whereUlid('report')
    ->middleware(['throttle:60,1', 'cache.headers:no_store'])->name('audit.seals.verify');

Route::middleware(['auth', 'throttle:60,1', 'cache.headers:private;no_store'])->prefix('business')->name('business.audit-reports.')->group(function (): void {
    Route::get('audit-report-operations/{request_id}', [BusinessAuditReportController::class, 'operation'])->whereUuid('request_id')->name('operations.show');
    Route::get('{business}/audit-reports/{report}', [BusinessAuditReportController::class, 'show'])->whereUlid(['business', 'report'])->name('show');
    Route::post('{business}/audit-reports/{report}/cosign', [BusinessAuditReportController::class, 'cosign'])->whereUlid(['business', 'report'])->name('cosign');
    Route::post('{business}/audit-reports/{report}/dispute', [AuditDisputeController::class, 'dispute'])->whereUlid(['business', 'report'])->name('dispute');
    Route::get('{business}/audit-reports/{report}/dispute/proofs/{proof}', [AuditDisputeController::class, 'proof'])->whereUlid(['business', 'report', 'proof'])->defaults('review_role', 'business')->name('disputes.proofs.show');
});

Route::post('email/verify-code', [EmailVerificationCodeController::class, 'store'])
    ->middleware(['auth', 'throttle:6,1'])->name('verification.code');

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
    Route::get('auditor', AuditorHomeController::class)->middleware(['throttle:60,1', 'cache.headers:private;no_store'])->name('auditor.home');
    Route::get('admin', StaffHomeController::class)->name('admin.home');
    Route::get('auditor/profile', [AuditorProfileController::class, 'show'])->name('auditor.profile');
});

Route::middleware(['auth', 'verified', 'throttle:60,1', 'cache.headers:private;no_store'])->prefix('investor')->name('investor.')->group(function (): void {
    Route::get('deals', [InvestorDealsController::class, 'index'])->name('deals');
    Route::get('verification', [InvestorVerificationController::class, 'show'])->name('verification');
    Route::post('verification/steps', [InvestorVerificationController::class, 'save'])->name('verification.save');
    Route::post('verification/documents', [InvestorVerificationController::class, 'upload'])->name('verification.upload');
    Route::post('verification/submit', [InvestorVerificationController::class, 'submit'])->name('verification.submit');
    Route::get('verified', [InvestorProfileController::class, 'verified'])->name('verified');
    Route::get('portfolio', [InvestorPortfolioController::class, 'show'])->name('portfolio');
    Route::get('profile', [InvestorProfileController::class, 'show'])->name('profile');
    Route::get('market', [InvestorMarketController::class, 'market'])->name('market');
    Route::get('cart', [InvestorMarketController::class, 'cart'])->name('cart');
    Route::get('deals/{campaign}', [InvestorDealsController::class, 'show'])->whereUlid('campaign')->name('deals.show');
    Route::get('wallet', [InvestorWalletController::class, 'show'])->name('wallet');
    Route::post('wallet/deposits', [InvestorWalletController::class, 'deposit'])->name('wallet.deposit');
    Route::get('wallet-operations/{request_id}', [InvestorWalletController::class, 'operation'])->whereUuid('request_id')->name('wallet.operations.show');
    Route::post('deals/{campaign}/reservations', [InvestorPrimaryController::class, 'reserve'])->whereUlid('campaign')->name('primary.reserve');
    Route::post('reservations/{reservation}/confirm', [InvestorPrimaryController::class, 'confirm'])->whereUlid('reservation')->name('primary.confirm');
    Route::post('reservations/{reservation}/release', [InvestorPrimaryController::class, 'release'])->whereUlid('reservation')->name('primary.release');
    Route::get('commitments/{commitment}', [InvestorPrimaryController::class, 'commitment'])->whereUlid('commitment')->name('commitments.show');
    Route::post('commitments/{commitment}/cancel', [InvestorPrimaryController::class, 'cancel'])->whereUlid('commitment')->name('primary.cancel');
    Route::get('deals/{campaign}/primary-operations/{request_id}', [InvestorPrimaryController::class, 'operation'])->whereUlid('campaign')->whereUuid('request_id')
        ->name('primary.operations.show');
});

Route::middleware(['auth', 'throttle:60,1'])->prefix('auditor')->name('auditor.')->group(function (): void {
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
    Route::get('portfolio', [AuditorPortfolioController::class, 'index'])->middleware('cache.headers:private;no_store')->name('portfolio.index');
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

Route::middleware(['auth', 'throttle:60,1', 'cache.headers:private;no_store'])->prefix('business')->name('business.repayments.')->group(function (): void {
    Route::get('{business}/repayments', [BusinessRepaymentController::class, 'show'])->whereUlid('business')->name('show');
    Route::post('{business}/repayments', [BusinessRepaymentController::class, 'pay'])->whereUlid('business')->name('pay');
    Route::get('{business}/repayment-operations/{request_id}', [BusinessRepaymentController::class, 'operation'])->whereUlid('business')->whereUuid('request_id')->name('operations.show');
});

Route::get('business/{business}', [BusinessHomeController::class, 'show'])->middleware(['auth', 'throttle:60,1', 'cache.headers:private;no_store'])
    ->whereUlid('business')->name('business.show');

Route::middleware(['auth', 'throttle:60,1', 'cache.headers:private;no_store'])->prefix('business')->name('business.')->group(function (): void {
    Route::get('{business}/reports', [BusinessHomeController::class, 'reports'])->whereUlid('business')->name('reports');
    Route::get('{business}/profile/{section?}', [BusinessHomeController::class, 'profile'])->whereUlid('business')->whereIn('section', ['company'])->name('profile');
    Route::get('{business}/rating', [BusinessHomeController::class, 'rating'])->whereUlid('business')->name('rating');
});

Route::middleware(['auth', 'throttle:60,1', 'cache.headers:private;no_store'])->prefix('business')->name('business.wallet.')->group(function (): void {
    Route::get('{business}/wallet', [BusinessWalletController::class, 'show'])->whereUlid('business')->name('show');
    Route::post('{business}/wallet/deposits', [BusinessWalletController::class, 'deposit'])->whereUlid('business')->name('deposit');
    Route::get('{business}/wallet-operations/{request_id}', [BusinessWalletController::class, 'operation'])->whereUlid('business')->whereUuid('request_id')->name('operations.show');
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

Route::middleware(['auth', 'throttle:60,1', 'cache.headers:private;no_store'])->prefix('admin/disbursements')->name('staff.disbursements.')->group(function (): void {
    Route::get('/', [StaffDisbursementController::class, 'index'])->name('index');
    Route::get('operations/{request_id}', [StaffDisbursementController::class, 'operation'])->whereUuid('request_id')->name('operations.show');
    Route::get('{disbursement}', [StaffDisbursementController::class, 'show'])->whereUlid('disbursement')->name('show');
    Route::post('{disbursement}/step-up', [StaffDisbursementController::class, 'stepUp'])->whereUlid('disbursement')
        ->middleware('throttle:disbursement-step-up')->name('step-up');
    foreach (['authorize' => 'authorize', 'approve' => 'approve', 'reject' => 'reject', 'hold' => 'hold', 'release-hold' => 'release_hold', 'requery' => 'requery'] as $path => $command) {
        Route::post('{disbursement}/'.$path, [StaffDisbursementController::class, 'command'])->whereUlid('disbursement')->defaults('command', $command)->name($path);
    }
});

Route::middleware(['auth', 'throttle:60,1'])->prefix('identity')->name('identity.')->group(function (): void {
    Route::post('people/resolve', [IdentityManagementController::class, 'resolvePerson'])->name('people.resolve');
    Route::post('staff-people', [IdentityManagementController::class, 'staffPerson'])->name('staff-people.record');
    Route::post('memberships', [IdentityManagementController::class, 'membership'])->name('memberships.update');
    Route::post('active-role', [IdentityManagementController::class, 'selectRole'])->name('active-role.store');
    Route::get('roles/{role}', [IdentityManagementController::class, 'role'])->name('roles.show');
    Route::get('bookmarks/{role}', [RoleBookmarkController::class, 'show'])->name('bookmarks.show');
    Route::post('bookmarks', [RoleBookmarkController::class, 'store'])->name('bookmarks.store');
    Route::get('roles/{role}/resume', [RoleBookmarkController::class, 'resume'])->name('roles.resume');
});

require __DIR__.'/settings.php';

require __DIR__.'/preview.php';

Route::middleware(['auth', 'throttle:60,1', 'cache.headers:private;no_store'])->group(function (): void {
    Route::get('admin/application-operations/{request_id}', [StaffApplicationReleaseController::class, 'operation'])
        ->whereUuid('request_id')->name('staff.applications.operations.show');
    Route::get('admin/applications', [StaffApplicationReleaseController::class, 'index'])->name('staff.applications.index');
    Route::get('admin/applications/{application}', [StaffApplicationReleaseController::class, 'show'])
        ->whereUlid('application')->name('staff.applications.show');
    Route::post('admin/applications/{application}/release', [StaffApplicationReleaseController::class, 'release'])
        ->whereUlid('application')->name('staff.applications.release');
    Route::get('business/{business}/applications/{application}/publish', [BusinessPublicationController::class, 'show'])
        ->whereUlid(['business', 'application'])->name('business.applications.publish.show');
    Route::post('business/{business}/applications/{application}/publish', [BusinessPublicationController::class, 'publish'])
        ->whereUlid(['business', 'application'])->name('business.applications.publish');
    Route::get('business/{business}/campaigns/{campaign}', [BusinessPublicationController::class, 'campaign'])
        ->whereUlid(['business', 'campaign'])->name('business.campaigns.show');
    Route::post('business/{business}/campaigns/{campaign}/cancel', [BusinessPublicationController::class, 'cancel'])
        ->whereUlid(['business', 'campaign'])->name('business.campaigns.cancel');
});

Route::get('admin/{section}', [StaffSectionController::class, 'show'])->whereIn('section', array_keys(StaffSectionController::SECTIONS))
    ->middleware(['auth', 'throttle:60,1', 'cache.headers:private;no_store'])->name('staff.sections.show');

Route::get('admin/investors', [StaffInvestorDirectoryController::class, 'index'])
    ->middleware(['auth', 'throttle:60,1', 'cache.headers:private;no_store'])->name('staff.investors.index');

Route::get('admin/businesses', [StaffBusinessDirectoryController::class, 'index'])
    ->middleware(['auth', 'throttle:60,1', 'cache.headers:private;no_store'])->name('staff.businesses.index');

Route::middleware(['auth', 'throttle:60,1', 'cache.headers:private;no_store'])->prefix('admin/auditors')->name('staff.auditors.')->group(function (): void {
    Route::get('/', [StaffAuditorDirectoryController::class, 'index'])->name('index');
    foreach (['approve', 'reject'] as $decision) {
        Route::post('{party}/licence/'.$decision, [StaffAuditorDirectoryController::class, 'decide'])->whereUlid('party')->defaults('decision', $decision)->name('licence.'.$decision);
    }
});

Route::middleware(['auth', 'throttle:60,1', 'cache.headers:private;no_store'])->group(function (): void {
    Route::get('admin/dashboard', StaffDashboardController::class)->name('staff.dashboard');
    Route::get('admin/staff', [StaffDirectoryController::class, 'index'])->name('staff.staff.index');
    Route::get('admin/activity', [StaffActivityController::class, 'index'])->name('staff.events.index');
});

Route::middleware(['auth', 'throttle:60,1', 'cache.headers:private;no_store'])->prefix('admin/investor-verifications')->name('staff.investor-verifications.')->group(function (): void {
    Route::get('/', [StaffInvestorVerificationController::class, 'index'])->name('index');
    Route::get('{verification}/documents/{document}', [StaffInvestorVerificationController::class, 'document'])
        ->whereUlid(['verification', 'document'])->name('document');
    Route::post('{verification}/approve', [StaffInvestorVerificationController::class, 'approve'])->whereUlid('verification')->name('approve');
    Route::post('{verification}/reject', [StaffInvestorVerificationController::class, 'reject'])->whereUlid('verification')->name('reject');
});

// Staging only (404 elsewhere) and superadmin only, both before any form request validates.
Route::middleware(['auth', 'throttle:60,1', 'cache.headers:private;no_store', EnsureStagingMailTesterAccess::class])->prefix('admin/staging-mail-testers')->name('staff.staging-mail-testers.')->group(function (): void {
    Route::get('/', [StaffStagingMailTesterController::class, 'index'])->name('index');
    Route::post('/', [StaffStagingMailTesterController::class, 'store'])->name('store');
    Route::post('{tester}/remove', [StaffStagingMailTesterController::class, 'remove'])->whereUlid('tester')->name('remove');
});

/*
 * The online propagation beacon (S4-E): which of the caller's topics changed, authorized at every
 * read. Polled by open pages, so it has its own per-account limit.
 */
Route::middleware(['auth', 'throttle:changes', 'cache.headers:private;no_store'])->group(function (): void {
    Route::get('changes', [ChangeFeedController::class, 'index'])->name('changes.index');
    Route::get('admin/changes', [ChangeFeedController::class, 'index'])->name('staff.changes.index');
});
