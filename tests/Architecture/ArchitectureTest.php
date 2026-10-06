<?php

declare(strict_types=1);

use App\Application\Auditor\Contracts\AuditReportCryptography;
use App\Application\Auditor\Contracts\AuditStepUp;
use App\Application\Identity\Contracts\Authenticator;
use App\Application\Identity\RegisterIdentity;
use App\Application\Wallet\ApplyProviderOutcome;
use App\Application\Wallet\Contracts\DepositProvider;
use App\Application\Wallet\Contracts\SyntheticEventSigner;
use App\Application\Wallet\Contracts\SyntheticWalletFixtures;
use App\Application\Wallet\Contracts\WalletPostings;
use App\Application\Wallet\Contracts\WalletStore;
use App\Application\Wallet\DispatchDepositIntents;
use App\Application\Wallet\FindWalletOperation;
use App\Application\Wallet\GetInvestorWallet;
use App\Application\Wallet\RecordDepositIntent;
use App\Http\Controllers\Controller;
use App\Models\AuditAssignment;
use App\Models\AuditAssignmentVersion;
use App\Models\AuditConflictDeclaration;
use App\Models\AuditEngagementAcceptance;
use App\Models\AuditEngagementRelease;
use App\Models\AuditLedgerExtraction;
use App\Models\AuditLedgerOriginal;
use App\Models\AuditLocation;
use App\Models\AuditLocationVersion;
use App\Models\AuditorCertificate;
use App\Models\AuditorIndependenceReview;
use App\Models\AuditorIndependenceVersion;
use App\Models\AuditorProfile;
use App\Models\AuditorProfileVersion;
use App\Models\AuditReport;
use App\Models\AuditReportPublication;
use App\Models\AuditReportSeal;
use App\Models\AuditReportSignature;
use App\Models\AuditReportVersion;
use App\Models\AuditSigningKey;
use App\Models\AuditSigningKeyRevocation;
use App\Models\AuditSourceSnapshot;
use App\Models\AuditStepUpProof;
use App\Models\BusinessApplication;
use App\Models\BusinessApplicationQuote;
use App\Models\BusinessApplicationRelease;
use App\Models\BusinessApplicationSignature;
use App\Models\BusinessApplicationSubmission;
use App\Models\BusinessApplicationVersion;
use App\Models\BusinessCampaign;
use App\Models\BusinessCampaignClosure;
use App\Models\BusinessCreditSnapshot;
use App\Models\BusinessExposureReservation;
use App\Models\BusinessMandate;
use App\Models\BusinessProfile;
use App\Models\CommandOperation;
use App\Models\ConsentRelease;
use App\Models\DepositPolicy;
use App\Models\InvestorAccountRestriction;
use App\Models\InvestorFundingMethod;
use App\Models\InvestorWallet;
use App\Models\LedgerAccount;
use App\Models\LedgerEntry;
use App\Models\LedgerLine;
use App\Models\Party;
use App\Models\RoleMembership;
use App\Models\StatementEvidence;
use App\Models\StatementExtraction;
use App\Models\StatementOriginal;
use App\Models\StatementTranscription;
use App\Models\StatementVerification;
use App\Models\VerifiedOrganizationIdentity;
use App\Models\WalletDepositCredit;
use App\Models\WalletDepositDispatch;
use App\Models\WalletDepositIntent;
use App\Models\WalletProviderEvent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Resources\Json\JsonResource;
use Jose\Component\Core\JWK;
use Symfony\Component\Finder\Finder;

/*
 * Executable form of the ADR-0001 boundaries.
 *
 * Layer rules cover namespaces. Frozen signing entry points additionally have
 * explicit callers and existence checks, so moving or removing a protected
 * symbol cannot silently turn its rule into a vacuous pass.
 *
 * Section 11.2's sixth boundary - the protected ledger, settlement,
 * underwriting-publication seams - arrives with those modules. The concrete
 * Auditor seal and publication boundaries are enforced below.
 */

// ---------------------------------------------------------------------------
// Global first-party PHP
// ---------------------------------------------------------------------------

arch('application symbols follow their PSR-4 path casing')
    ->expect('App')
    ->toBeCasedCorrectly();

arch('release code excludes debug and termination helpers')
    ->expect(['dd', 'dump', 'die', 'var_dump', 'var_export', 'print_r', 'ray'])
    ->not->toBeUsed();

arch('release code reads configuration rather than the environment')
    ->expect('env')
    ->not->toBeUsed()
    ->ignoring('config');

// ---------------------------------------------------------------------------
// HTTP, Inertia and API transport
// ---------------------------------------------------------------------------

arch('controllers use the application controller convention')
    ->expect('App\Http\Controllers')
    ->classes()
    ->toExtend(Controller::class)
    ->toHaveSuffix('Controller')
    ->ignoring(Controller::class);

arch('form requests own transport validation')
    ->expect('App\Http\Requests')
    ->toExtend(FormRequest::class)
    ->toHaveSuffix('Request');

arch('controllers delegate governed behaviour to the application layer')
    ->expect('App\Http\Controllers')
    ->not->toUse([
        'App\Domain',
        'App\Infrastructure',
        'App\Models',
        'Illuminate\Database',
        'Illuminate\Support\Facades\DB',
    ]);

// ---------------------------------------------------------------------------
// Application and Domain
// ---------------------------------------------------------------------------

arch('domain code is independent from frameworks and outer layers')
    ->expect('App\Domain')
    ->not->toUse([
        'App\Application',
        'App\Http',
        'App\Infrastructure',
        'App\Models',
        'Illuminate',
        'Inertia',
        'Laravel',
    ]);

arch('domain results depend only on their inputs')
    ->expect('App\Domain')
    ->not->toUse([
        // A rule that reads the clock or the random source cannot be replayed,
        // which is what makes a historical calculation auditable.
        'now', 'today', 'time', 'date', 'microtime',
        'rand', 'mt_rand', 'random_int', 'random_bytes', 'uniqid', 'shuffle', 'array_rand',
    ]);

arch('application code orchestrates through contracts rather than persistence')
    ->expect('App\Application')
    ->not->toUse([
        'App\Http',
        'App\Infrastructure',
        'App\Models',
        'Illuminate\Database',
        'Illuminate\Support\Facades\DB',
        'Inertia',
    ]);

// ---------------------------------------------------------------------------
// Eloquent API Resources
// ---------------------------------------------------------------------------

arch('eloquent resources only shape authorized output')
    ->expect('App\Http\Resources')
    ->toExtend(JsonResource::class)
    ->toHaveSuffix('Resource')
    ->not->toUse([
        'App\Actions',
        'App\Application',
        'App\Domain',
        'App\Infrastructure',
        'App\Models',
        'App\Support',
        'Illuminate\Database',
        'Illuminate\Support\Facades\DB',
    ]);

// ---------------------------------------------------------------------------
// Integrations and providers
// ---------------------------------------------------------------------------

arch('infrastructure stays independent from delivery transports')
    ->expect('App\Infrastructure')
    ->not->toUse([
        'App\Http',
        'Inertia',
    ]);

arch('infrastructure concretions are reached through their container binding')
    // Naming an adapter anywhere else is how a provider implementation leaks
    // past the port that is supposed to hide it.
    ->expect('App\Infrastructure')
    ->toOnlyBeUsedIn('App\Providers')
    ->ignoring(['App\Infrastructure\Business\RetainedFundedCampaignFacts', 'App\Infrastructure\Business\CampaignProgress',
        'App\Infrastructure\Primary\RetainedPrimaryReservation', 'App\Infrastructure\Primary\RetainedHeldClaimRelease']);

arch('campaign progress is private to the Business campaign page and the Investor deals')
    ->expect('App\Infrastructure\Business\CampaignProgress')
    ->toOnlyBeUsedIn(['App\Infrastructure\Business\EloquentBusinessCampaignStore', 'App\Infrastructure\Business\RetainedInvestorDeals']);

arch('retained reservation replay is private to the two named Primary consumers')
    ->expect('App\Infrastructure\Primary\RetainedPrimaryReservation')
    ->toOnlyBeUsedIn(['App\Infrastructure\Primary\EloquentPrimaryReservations', 'App\Infrastructure\Primary\RetainedHeldClaimRelease']);

arch('held retirement proof is private to allocation and the readonly summary')
    ->expect('App\Infrastructure\Primary\RetainedHeldClaimRelease')
    ->toOnlyBeUsedIn(['App\Infrastructure\Primary\EloquentPrimaryReservations', 'App\Infrastructure\Primary\EloquentCampaignReservationSummary']);

arch('held retirement collaborators keep Business and Wallet persistence behind their ports')
    ->expect(['App\Infrastructure\Primary\RetainedPrimaryReservation', 'App\Infrastructure\Primary\RetainedHeldClaimRelease'])
    ->not->toUse(['App\Models\BusinessCampaign', 'App\Models\InvestorWallet', 'App\Models\LedgerEntry',
        'App\Models\LedgerAccount', 'App\Application\Business\Contracts\PublishedCampaignEvidence']);

arch('the retained funded projection is an internal collaborator of the named funding adapter')
    ->expect('App\Infrastructure\Business\RetainedFundedCampaignFacts')
    ->toOnlyBeUsedIn(['App\Providers', 'App\Infrastructure\Primary\EloquentFundedCampaigns']);

arch('the retained funded projection keeps its own collaborators behind contracts')
    ->expect('App\Infrastructure\Business\RetainedFundedCampaignFacts')
    ->not->toUse('App\Infrastructure');

it('has concrete targets for the identity persistence boundary', function (): void {
    expect(class_exists(Party::class))->toBeTrue()
        ->and(class_exists(RoleMembership::class))->toBeTrue()
        ->and(class_exists(VerifiedOrganizationIdentity::class))->toBeTrue()
        ->and(class_exists(ConsentRelease::class))->toBeTrue()
        ->and(class_exists(RegisterIdentity::class))->toBeTrue();
})->group('arch');

arch('identity records are only accessed by their adapter and model relationships')
    ->expect(['App\Models\Party', 'App\Models\RoleMembership', 'App\Models\VerifiedPersonIdentity', 'App\Models\VerifiedOrganizationIdentity', 'App\Models\IdentityOperator', 'App\Models\IdentityAuditEvent', 'App\Models\StaffAccount', 'App\Models\StaffPersonIdentity', 'App\Models\RoleBookmark', 'App\Models\ConsentRelease'])
    ->toOnlyBeUsedIn(['App\Infrastructure\Identity', 'App\Models', 'Database\Factories']);

it('has concrete targets for the command outcome boundary', function (): void {
    expect(class_exists(CommandOperation::class))->toBeTrue();
})->group('arch');

arch('command outcomes are only accessed by the journal adapter')
    ->expect('App\Models\CommandOperation')
    ->toOnlyBeUsedIn(['App\Infrastructure\Operations', 'App\Models', 'Database\Factories']);

it('has concrete targets for the business authority boundary', function (): void {
    expect(class_exists(BusinessProfile::class))->toBeTrue()
        ->and(class_exists(BusinessMandate::class))->toBeTrue()
        ->and(class_exists(BusinessApplication::class))->toBeTrue()
        ->and(class_exists(BusinessApplicationVersion::class))->toBeTrue()
        ->and(class_exists(BusinessApplicationQuote::class))->toBeTrue()
        ->and(class_exists(BusinessApplicationSignature::class))->toBeTrue()
        ->and(class_exists(BusinessApplicationSubmission::class))->toBeTrue()
        ->and(class_exists(BusinessExposureReservation::class))->toBeTrue()
        ->and(class_exists(BusinessApplicationRelease::class))->toBeTrue()
        ->and(class_exists(BusinessCampaign::class))->toBeTrue()
        ->and(class_exists(BusinessCampaignClosure::class))->toBeTrue()
        ->and(class_exists(BusinessCreditSnapshot::class))->toBeTrue();
})->group('arch');

arch('business authority records are only accessed by their adapter')
    ->expect(['App\Models\BusinessMandate', 'App\Models\BusinessApplication', 'App\Models\BusinessApplicationVersion', 'App\Models\BusinessCreditSnapshot', 'App\Models\BusinessApplicationQuote', 'App\Models\BusinessApplicationSignature', 'App\Models\BusinessApplicationSubmission', 'App\Models\BusinessExposureReservation', 'App\Models\BusinessApplicationRelease', 'App\Models\BusinessCampaignClosure'])
    ->toOnlyBeUsedIn(['App\Infrastructure\Business', 'App\Models', 'Database\Factories']);

arch('business authority records are only accessed by their adapter or the named funding gate reader')
    ->expect(['App\Models\BusinessProfile', 'App\Models\BusinessCampaign'])
    ->toOnlyBeUsedIn(['App\Infrastructure\Business', 'App\Infrastructure\Primary\EloquentFundedCampaigns', 'App\Models', 'Database\Factories']);

arch('declared Business connection evidence stays inside financial persistence adapters')
    ->expect('App\Application\Business\Contracts\BusinessConnections')
    ->toOnlyBeUsedIn(['App\Infrastructure\Business', 'App\Infrastructure\Primary', 'App\Infrastructure\Disbursement', 'App\Providers\AppServiceProvider']);

it('has concrete targets for the immutable statement evidence boundary', function (): void {
    expect(class_exists(StatementEvidence::class))->toBeTrue()
        ->and(class_exists(StatementOriginal::class))->toBeTrue()
        ->and(class_exists(StatementExtraction::class))->toBeTrue()
        ->and(class_exists(StatementTranscription::class))->toBeTrue()
        ->and(class_exists(StatementVerification::class))->toBeTrue();
})->group('arch');

arch('statement evidence records are only accessed by their adapter')
    ->expect(['App\Models\StatementEvidence', 'App\Models\StatementOriginal', 'App\Models\StatementExtraction', 'App\Models\StatementTranscription', 'App\Models\StatementVerification'])
    ->toOnlyBeUsedIn(['App\Infrastructure\Evidence', 'App\Models', 'Database\Factories']);

it('has concrete targets for the auditor accreditation boundary', function (): void {
    expect(class_exists(AuditorProfile::class))->toBeTrue()
        ->and(class_exists(AuditorProfileVersion::class))->toBeTrue()
        ->and(class_exists(AuditLocation::class))->toBeTrue()
        ->and(class_exists(AuditLocationVersion::class))->toBeTrue()
        ->and(class_exists(AuditorIndependenceReview::class))->toBeTrue()
        ->and(class_exists(AuditorIndependenceVersion::class))->toBeTrue()
        ->and(class_exists(AuditAssignment::class))->toBeTrue()
        ->and(class_exists(AuditAssignmentVersion::class))->toBeTrue()
        ->and(class_exists(AuditConflictDeclaration::class))->toBeTrue()
        ->and(class_exists(AuditReport::class))->toBeTrue()
        ->and(class_exists(AuditReportVersion::class))->toBeTrue()
        ->and(class_exists(AuditReportPublication::class))->toBeTrue()
        ->and(class_exists(AuditReportSignature::class))->toBeTrue()
        ->and(class_exists(AuditReportSeal::class))->toBeTrue()
        ->and(class_exists(AuditSigningKey::class))->toBeTrue()
        ->and(class_exists(AuditSigningKeyRevocation::class))->toBeTrue()
        ->and(class_exists(AuditStepUpProof::class))->toBeTrue()
        ->and(class_exists(AuditLedgerOriginal::class))->toBeTrue()
        ->and(class_exists(AuditLedgerExtraction::class))->toBeTrue()
        ->and(class_exists(AuditSourceSnapshot::class))->toBeTrue()
        ->and(class_exists(AuditEngagementRelease::class))->toBeTrue()
        ->and(class_exists(AuditEngagementAcceptance::class))->toBeTrue()
        ->and(class_exists(AuditorCertificate::class))->toBeTrue();
})->group('arch');

arch('auditor accreditation records are only accessed by their adapter')
    ->expect(['App\Models\AuditorProfile', 'App\Models\AuditorProfileVersion', 'App\Models\AuditorCertificate', 'App\Models\AuditLocation', 'App\Models\AuditLocationVersion', 'App\Models\AuditorIndependenceReview', 'App\Models\AuditorIndependenceVersion', 'App\Models\AuditAssignment', 'App\Models\AuditAssignmentVersion', 'App\Models\AuditConflictDeclaration', 'App\Models\AuditReport', 'App\Models\AuditReportVersion', 'App\Models\AuditLedgerOriginal', 'App\Models\AuditLedgerExtraction', 'App\Models\AuditSourceSnapshot', 'App\Models\AuditEngagementRelease', 'App\Models\AuditEngagementAcceptance', 'App\Models\AuditPublicationEvent', 'App\Models\AuditDisputeProof', 'App\Models\AuditReportPublication', 'App\Models\AuditReportSignature', 'App\Models\AuditReportSeal', 'App\Models\AuditSigningKey', 'App\Models\AuditSigningKeyRevocation', 'App\Models\AuditStepUpProof'])
    ->toOnlyBeUsedIn(['App\Infrastructure\Auditor', 'App\Models', 'Database\Factories']);

it('has concrete targets for the audit signing entry points', function (): void {
    expect(interface_exists(AuditReportCryptography::class))->toBeTrue()
        ->and(interface_exists(AuditStepUp::class))->toBeTrue()
        ->and(interface_exists(Authenticator::class))->toBeTrue()
        ->and(class_exists(JWK::class))->toBeTrue();
})->group('arch');

arch('audit signing cryptography is only reached through authorized report stores')
    ->expect('App\Application\Auditor\Contracts\AuditReportCryptography')
    ->toOnlyBeUsedIn(['App\Infrastructure\Auditor\EloquentAuditReportStore', 'App\Infrastructure\Auditor\EloquentAuditReportPublicationStore',
        'App\Infrastructure\Auditor\JoseAuditReportCryptography', 'App\Providers\AppServiceProvider']);

arch('audit signing step up is only reached through the report store')
    ->expect('App\Application\Auditor\Contracts\AuditStepUp')
    ->toOnlyBeUsedIn(['App\Infrastructure\Auditor\EloquentAuditReportStore', 'App\Infrastructure\Auditor\EloquentAuditStepUp', 'App\Providers\AppServiceProvider']);

arch('audit signing authenticator verification uses its identity entry point')
    ->expect('App\Application\Identity\Contracts\Authenticator')
    ->toOnlyBeUsedIn(['App\Application\Identity\VerifyAuthenticator', 'App\Infrastructure\Identity\FortifyAuthenticator', 'App\Providers\AppServiceProvider']);

arch('audit signing JOSE primitives stay inside the cryptographic adapter')
    ->expect(['Jose\Component\Core\JWK', 'Jose\Component\Core\AlgorithmManager', 'Jose\Component\Signature\Algorithm\ES256',
        'Jose\Component\Signature\JWSBuilder', 'Jose\Component\Signature\JWSVerifier', 'Jose\Component\Signature\Serializer\CompactSerializer',
        'Jose\Component\KeyManagement\JWKFactory'])
    ->toOnlyBeUsedIn(['App\Infrastructure\Auditor\JoseAuditReportCryptography', 'Database\Factories\AuditSigningKeyFactory']);

it('keeps every audit signing JOSE namespace reference inside the adapter or synthetic key factory', function (): void {
    $root = dirname(__DIR__, 2);
    $allowed = ['app/Infrastructure/Auditor/JoseAuditReportCryptography.php', 'database/factories/AuditSigningKeyFactory.php'];
    $violations = [];
    // Inspect names without loading optional JOSE encryption algorithms and their optional dependencies.
    foreach (Finder::create()->files()->in([$root.'/app', $root.'/database'])->name('*.php') as $file) {
        $relative = substr($file->getPathname(), strlen($root) + 1);
        if (in_array($relative, $allowed, true)) {
            continue;
        }
        foreach (token_get_all($file->getContents()) as $token) {
            if (is_array($token) && in_array($token[0], [T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED], true)) {
                $name = ltrim($token[1], '\\');
                if ($name === 'Jose' || str_starts_with($name, 'Jose\\')) {
                    $violations[] = $relative.':'.$token[2];
                }
            }
        }
    }
    expect($violations)->toBe([]);
})->group('arch');

arch('accepted application input is private to the campaign adapter')
    ->expect('App\\Application\\Business\\Contracts\\AcceptedApplicationStore')
    ->toOnlyBeUsedIn(['App\\Infrastructure\\Business', 'App\\Providers\\AppServiceProvider']);

arch('release report input is private to the protected Business and Auditor adapters')
    ->expect('App\\Application\\Auditor\\Contracts\\PublishedApplicationReport')
    ->toOnlyBeUsedIn(['App\\Infrastructure\\Business', 'App\\Infrastructure\\Auditor', 'App\\Providers\\AppServiceProvider']);

arch('accepted exposure is private to the Business adapter')
    ->expect('App\\Application\\Business\\Contracts\\BusinessExposureStore')
    ->toOnlyBeUsedIn(['App\\Infrastructure\\Business', 'App\\Providers\\AppServiceProvider']);

arch('campaign closure evidence is private to the Business adapter')
    ->expect('App\\Application\\Business\\Contracts\\CampaignClosureEvidence')
    ->toOnlyBeUsedIn(['App\\Infrastructure\\Business', 'App\\Providers\\AppServiceProvider']);

arch('retained campaign inputs are private to the Business and Primary adapters')
    ->expect('App\Application\Business\Contracts\PrimaryCampaignSource')
    ->toOnlyBeUsedIn(['App\Infrastructure\Business', 'App\Infrastructure\Primary', 'App\Providers\AppServiceProvider']);

arch('full publication evidence remains private to the Business adapter')
    ->expect('App\Application\Business\Contracts\PublishedCampaignEvidence')
    ->toOnlyBeUsedIn(['App\Infrastructure\Business', 'App\Providers\AppServiceProvider']);

arch('campaign commitment evidence stays private to the Business and Primary adapters')
    ->expect('App\\Application\\Primary\\Contracts\\CampaignCommitments')
    ->toOnlyBeUsedIn(['App\\Infrastructure\\Business', 'App\\Infrastructure\\Primary', 'App\\Providers\\AppServiceProvider']);

arch('campaign reservation aggregates remain private to the Business and Primary adapters')
    ->expect('App\\Application\\Primary\\Contracts\\CampaignReservationSummary')
    ->toOnlyBeUsedIn(['App\\Infrastructure\\Business', 'App\\Infrastructure\\Primary', 'App\\Providers\\AppServiceProvider']);

arch('Primary reservation and commitment records remain inside their persistence boundary')
    ->expect(['App\\Models\\PrimaryReservationRecord', 'App\\Models\\PrimaryReservationVersion', 'App\\Models\\PrimaryCommitment'])
    ->toOnlyBeUsedIn(['App\\Infrastructure\\Primary', 'App\\Models', 'Database\\Factories']);
it('has concrete targets for the wallet ledger boundary', function (): void {
    foreach ([InvestorWallet::class, LedgerAccount::class, LedgerEntry::class, LedgerLine::class, DepositPolicy::class, InvestorFundingMethod::class,
        InvestorAccountRestriction::class, WalletDepositIntent::class, WalletDepositDispatch::class, WalletProviderEvent::class, WalletDepositCredit::class,
        WalletStore::class, WalletPostings::class, DepositProvider::class, SyntheticEventSigner::class, SyntheticWalletFixtures::class,
        RecordDepositIntent::class, GetInvestorWallet::class, FindWalletOperation::class, ApplyProviderOutcome::class, DispatchDepositIntents::class] as $target) {
        expect(class_exists($target) || interface_exists($target))->toBeTrue($target);
    }
})->group('arch');

arch('wallet ledger records are only accessed by their adapter')
    ->expect(['App\Models\InvestorWallet', 'App\Models\LedgerAccount', 'App\Models\LedgerEntry', 'App\Models\LedgerLine', 'App\Models\DepositPolicy',
        'App\Models\InvestorFundingMethod', 'App\Models\InvestorAccountRestriction', 'App\Models\WalletDepositIntent', 'App\Models\WalletDepositDispatch',
        'App\Models\WalletProviderEvent', 'App\Models\WalletDepositCredit'])
    ->toOnlyBeUsedIn(['App\Infrastructure\Wallet', 'App\Models', 'Database\Factories']);

arch('Business wallet records are only accessed by the wallet adapter')
    ->expect(['App\Models\BusinessWallet', 'App\Models\BusinessFundingMethod', 'App\Models\BusinessDepositIntent', 'App\Models\BusinessDepositDispatch',
        'App\Models\BusinessProviderEvent', 'App\Models\BusinessDepositCredit', 'App\Models\BusinessRepayment'])
    ->toOnlyBeUsedIn(['App\Infrastructure\Wallet', 'App\Models', 'Database\Factories']);

arch('the Business wallet port is reached only through its actions and adapter')
    ->expect('App\Application\Wallet\Contracts\BusinessWalletStore')
    ->toOnlyBeUsedIn(['App\Application\Wallet', 'App\Infrastructure\Wallet', 'App\Providers\AppServiceProvider']);

arch('the servicing port is reached only by the wallet adapter and its implementations')
    ->expect('App\Application\Business\Contracts\NoteServicing')
    ->toOnlyBeUsedIn(['App\Infrastructure\Wallet', 'App\Infrastructure\Business', 'App\Providers\AppServiceProvider']);

arch('the deposit provider is reached only through the wallet actions and adapters')
    ->expect('App\Application\Wallet\Contracts\DepositProvider')
    ->toOnlyBeUsedIn(['App\Application\Wallet', 'App\Infrastructure\Wallet', 'App\Providers\AppServiceProvider']);

arch('synthetic signing and fixtures stay inside the local wallet hook')
    ->expect(['App\Application\Wallet\Contracts\SyntheticEventSigner', 'App\Application\Wallet\Contracts\SyntheticWalletFixtures'])
    ->toOnlyBeUsedIn(['App\Infrastructure\Wallet', 'App\Providers\AppServiceProvider', 'App\Console\Commands\PrepareSyntheticWallet']);

it('has concrete targets for the disbursement boundary', function (): void {
    foreach (['App\Models\Disbursement', 'App\Models\DisbursementEvent', 'App\Models\DisbursementStepUpProof', 'App\Models\DisbursementStepUpMarker',
        'App\Models\DisbursementIntent', 'App\Models\DisbursementDispatch', 'App\Models\DisbursementProviderCall', 'App\Models\DisbursementProviderEvent',
        'App\Models\DisbursementReconciliation', 'App\Models\DisbursementClosing', 'App\Models\PrimaryHolding',
        'App\Application\Disbursement\Contracts\DisbursementStore', 'App\Application\Disbursement\Contracts\FundedCampaigns',
        'App\Application\Disbursement\Contracts\PayoutDestinations', 'App\Application\Disbursement\Contracts\StaffConnections',
        'App\Application\Disbursement\Contracts\PayoutProvider', 'App\Application\Disbursement\Contracts\SyntheticDisbursementFixtures',
        'App\Application\Disbursement\Contracts\SyntheticPayoutScripts', 'App\Application\Disbursement\Contracts\DisbursementClosingEvidence'] as $target) {
        expect(class_exists($target) || interface_exists($target))->toBeTrue($target);
    }
})->group('arch');

arch('disbursement records are only accessed by the disbursement adapters')
    ->expect(['App\Models\Disbursement', 'App\Models\DisbursementEvent', 'App\Models\DisbursementStepUpProof', 'App\Models\DisbursementStepUpMarker',
        'App\Models\DisbursementIntent', 'App\Models\DisbursementDispatch', 'App\Models\DisbursementProviderCall', 'App\Models\DisbursementProviderEvent',
        'App\Models\DisbursementReconciliation', 'App\Models\DisbursementClosing', 'App\Models\DisbursementIndependenceDeclaration'])
    ->toOnlyBeUsedIn(['App\Infrastructure\Disbursement', 'App\Models', 'Database\Factories']);

arch('proposed Holdings stay unwritten until the S3-C adapter owns them')
    ->expect('App\Models\PrimaryHolding')
    ->toOnlyBeUsedIn(['App\Models', 'Database\Factories']);

it('keeps the Holding source verifier read-only', function (): void {
    $source = (string) file_get_contents(dirname(__DIR__, 2).'/app/Infrastructure/Primary/RetainedHoldingSource.php');
    expect(interface_exists('App\Application\Primary\Contracts\HoldingSource'))->toBeTrue()
        ->and($source)->not->toMatch('/->(insert|update|delete|upsert|save|forceFill|create|lockForUpdate|sharedLock|lock)\(|PrimaryHolding|DB::(statement|unprepared|transaction)/');
})->group('arch');

arch('funding sources are reached only through the disbursement and named Primary adapters')
    ->expect('App\Application\Disbursement\Contracts\FundedCampaigns')
    ->toOnlyBeUsedIn(['App\Infrastructure\Disbursement', 'App\Infrastructure\Primary\EloquentFundedCampaigns', 'App\Providers\AppServiceProvider']);

arch('destination and staff connection sources are reached only through the disbursement adapter')
    ->expect(['App\Application\Disbursement\Contracts\PayoutDestinations',
        'App\Application\Disbursement\Contracts\StaffConnections'])
    ->toOnlyBeUsedIn(['App\Infrastructure\Disbursement', 'App\Providers\AppServiceProvider']);

arch('the payout provider is reached only through the disbursement actions and adapters')
    ->expect('App\Application\Disbursement\Contracts\PayoutProvider')
    ->toOnlyBeUsedIn(['App\Application\Disbursement', 'App\Infrastructure\Disbursement', 'App\Providers\AppServiceProvider']);

arch('synthetic disbursement fixtures stay inside the local disbursement hook')
    ->expect(['App\Application\Disbursement\Contracts\SyntheticDisbursementFixtures', 'App\Application\Disbursement\Contracts\SyntheticPayoutScripts'])
    ->toOnlyBeUsedIn(['App\Infrastructure\Disbursement', 'App\Providers\AppServiceProvider', 'App\Console\Commands\PrepareSyntheticDisbursement']);
arch('funding cash evidence stays behind the wallet and Primary adapters')
    ->expect(['App\\Application\\Wallet\\Contracts\\PrimaryCommittedCash', 'App\\Application\\Wallet\\Contracts\\PrimaryCashReceipts'])
    ->toOnlyBeUsedIn(['App\\Infrastructure\\Wallet', 'App\\Infrastructure\\Primary', 'App\\Providers\\AppServiceProvider']);

arch('funding locks and evidence remain internal to Business and Primary persistence')
    ->expect(['App\Application\Primary\Contracts\PrimaryFunding', 'App\Application\Primary\Contracts\CampaignFundingEvidence'])
    ->toOnlyBeUsedIn(['App\Infrastructure\Business', 'App\Infrastructure\Primary', 'App\Providers\AppServiceProvider']);

arch('Holding source facts stay behind the Primary, disbursement and funded purchase projection adapters')
    ->expect('App\Application\Primary\Contracts\HoldingSource')
    ->toOnlyBeUsedIn(['App\Infrastructure\Primary', 'App\Infrastructure\Disbursement',
        'App\Infrastructure\Business\RetainedFundedCampaignFacts', 'App\Providers\AppServiceProvider']);

arch('funding records remain inside Primary persistence')
    ->expect('App\Models\PrimaryCampaignFunding')
    ->toOnlyBeUsedIn(['App\Infrastructure\Primary', 'App\Models', 'Database\Factories']);

arch('returned cash evidence stays behind the wallet and Primary adapters')
    ->expect('App\\Application\\Wallet\\Contracts\\PrimaryReturnedCash')
    ->toOnlyBeUsedIn(['App\\Infrastructure\\Wallet', 'App\\Infrastructure\\Primary', 'App\\Providers\\AppServiceProvider']);
