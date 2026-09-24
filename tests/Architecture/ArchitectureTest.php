<?php

declare(strict_types=1);

use App\Application\Identity\RegisterIdentity;
use App\Http\Controllers\Controller;
use App\Models\AuditorCertificate;
use App\Models\AuditorProfile;
use App\Models\AuditorProfileVersion;
use App\Models\BusinessApplication;
use App\Models\BusinessApplicationVersion;
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
use App\Models\VerifiedOrganizationIdentity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Resources\Json\JsonResource;

/*
 * Executable form of the ADR-0001 boundaries.
 *
 * Rules are stated over namespaces, never over individual classes. A rule that
 * names a class stops being architecture the moment that class is renamed or a
 * second one appears beside it, and it says nothing about the module added
 * next week. Everything below therefore holds for any module placed inside
 * these layers.
 *
 * Section 11.2's sixth boundary - the protected ledger, settlement,
 * underwriting-publication and seal seams - has no rules
 * here yet because those namespaces do not exist. They arrive with the module
 * that introduces them; writing rules over absent namespaces would report a
 * protection that is not there.
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
        ->and(class_exists(BusinessApplicationVersion::class))->toBeTrue();
})->group('arch');

arch('business authority records are only accessed by their adapter')
    ->expect(['App\Models\BusinessProfile', 'App\Models\BusinessMandate', 'App\Models\BusinessApplication', 'App\Models\BusinessApplicationVersion'])
    ->toOnlyBeUsedIn(['App\Infrastructure\Business', 'App\Models', 'Database\Factories']);

it('has concrete targets for the immutable statement evidence boundary', function (): void {
    expect(class_exists(StatementEvidence::class))->toBeTrue()
        ->and(class_exists(StatementOriginal::class))->toBeTrue()
        ->and(class_exists(StatementExtraction::class))->toBeTrue()
        ->and(class_exists(StatementTranscription::class))->toBeTrue();
})->group('arch');

arch('statement evidence records are only accessed by their adapter')
    ->expect(['App\Models\StatementEvidence', 'App\Models\StatementOriginal', 'App\Models\StatementExtraction', 'App\Models\StatementTranscription'])
    ->toOnlyBeUsedIn(['App\Infrastructure\Evidence', 'App\Models', 'Database\Factories']);

it('has concrete targets for the auditor accreditation boundary', function (): void {
    expect(class_exists(AuditorProfile::class))->toBeTrue()
        ->and(class_exists(AuditorProfileVersion::class))->toBeTrue()
        ->and(class_exists(AuditorCertificate::class))->toBeTrue();
})->group('arch');

arch('auditor accreditation records are only accessed by their adapter')
    ->expect(['App\Models\AuditorProfile', 'App\Models\AuditorProfileVersion', 'App\Models\AuditorCertificate'])
    ->toOnlyBeUsedIn(['App\Infrastructure\Auditor', 'App\Models', 'Database\Factories']);
