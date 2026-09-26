<?php

declare(strict_types=1);

use App\Application\Auditor\Contracts\AuditReportCryptography;
use App\Application\Auditor\Contracts\AuditStepUp;
use App\Application\Identity\Contracts\Authenticator;
use App\Application\Identity\RegisterIdentity;
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
use App\Models\BusinessApplicationSignature;
use App\Models\BusinessApplicationSubmission;
use App\Models\BusinessApplicationVersion;
use App\Models\BusinessCreditSnapshot;
use App\Models\BusinessMandate;
use App\Models\BusinessProfile;
use App\Models\CommandOperation;
use App\Models\ConsentRelease;
use App\Models\Party;
use App\Models\RoleMembership;
use App\Models\StatementEvidence;
use App\Models\StatementExtraction;
use App\Models\StatementOriginal;
use App\Models\StatementTranscription;
use App\Models\StatementVerification;
use App\Models\VerifiedOrganizationIdentity;
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
    ->toOnlyBeUsedIn('App\Providers');

it('has concrete targets for the identity persistence boundary', function (): void {
    expect(class_exists(Party::class))->toBeTrue()
        ->and(class_exists(RoleMembership::class))->toBeTrue()
        ->and(class_exists(VerifiedOrganizationIdentity::class))->toBeTrue()
        ->and(class_exists(ConsentRelease::class))->toBeTrue()
        ->and(class_exists(RegisterIdentity::class))->toBeTrue();
})->group('arch');

arch('identity records are only accessed by their adapter and model relationships')
    ->expect(['App\Models\Party', 'App\Models\RoleMembership', 'App\Models\VerifiedPersonIdentity', 'App\Models\VerifiedOrganizationIdentity', 'App\Models\IdentityOperator', 'App\Models\IdentityAuditEvent', 'App\Models\StaffAccount', 'App\Models\RoleBookmark', 'App\Models\ConsentRelease'])
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
        ->and(class_exists(BusinessCreditSnapshot::class))->toBeTrue();
})->group('arch');

arch('business authority records are only accessed by their adapter')
    ->expect(['App\Models\BusinessProfile', 'App\Models\BusinessMandate', 'App\Models\BusinessApplication', 'App\Models\BusinessApplicationVersion', 'App\Models\BusinessCreditSnapshot', 'App\Models\BusinessApplicationQuote', 'App\Models\BusinessApplicationSignature', 'App\Models\BusinessApplicationSubmission'])
    ->toOnlyBeUsedIn(['App\Infrastructure\Business', 'App\Models', 'Database\Factories']);

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
