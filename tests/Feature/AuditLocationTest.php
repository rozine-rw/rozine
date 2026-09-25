<?php

declare(strict_types=1);

use App\Application\Auditor\GetAuditLocation;
use App\Application\Auditor\MarkAuditLocationMoved;
use App\Application\Auditor\VerifyAuditLocation;
use App\Application\Business\WithBusinessReview;
use App\Application\Identity\ConfigureStaffAccess;
use App\Domain\Identity\IdentityViolation;
use App\Domain\Operations\CommandRejection;
use App\Models\AuditLocation;
use App\Models\AuditLocationVersion;
use App\Models\BusinessProfile;
use App\Models\CommandOperation;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\Support\AuditorFixture;
use Tests\Support\BusinessAuthorityFixture;

beforeEach(function (): void {
    $this->travelTo(now()->setDate(2026, 9, 24)->setTime(10, 0)->utc());
    $this->freezeTime();
});

/** @return array<string, mixed> */
function verifySyntheticAuditLocation(int $staffId, string $subjectId, string $kind = 'office', int $revision = 0, ?string $request = null): array
{
    return app(VerifyAuditLocation::class)->handle($staffId, $kind, $subjectId, $revision, '-1.9441000', '30.0619000', 20,
        now('UTC')->format('Y-m-d\TH:i:s\Z'), 'synthetic:reviewed-registered-location', 'Reviewed synthetic location evidence.', $request ?? (string) Str::uuid());
}

it('records a verified office as encrypted versioned evidence with a minimal replayable receipt', function (): void {
    $fixture = AuditorFixture::make();
    $staff = $fixture['staff']->id;
    $party = $fixture['party']->id;
    $read = app(GetAuditLocation::class);
    expect($read->handle($staff, 'office', $party)['revision'])->toBe(0);
    $this->assertDatabaseCount('audit_locations', 0);
    $key = (string) Str::uuid();
    $result = verifySyntheticAuditLocation($staff, $party, request: $key);
    $record = AuditLocation::query()->firstOrFail();
    $history = AuditLocationVersion::query()->firstOrFail();
    expect($result['code'])->toBe('AUDIT_LOCATION_VERIFIED')->and($result['revision'])->toBe(1)
        ->and(verifySyntheticAuditLocation($staff, $party, request: $key))->toBe($result)
        ->and($history->snapshot)->toBe($read->handle($staff, 'office', $party))
        ->and($record->state['point'])->toBe(['latitude' => '-1.9441000', 'longitude' => '30.0619000'])
        ->and($record->getRawOriginal('state'))->not->toContain('-1.9441000', 'synthetic:reviewed')
        ->and($history->getRawOriginal('snapshot'))->not->toContain('30.0619000')
        ->and($history->getRawOriginal('reason'))->not->toContain('synthetic location')
        ->and($record->toArray())->not->toHaveKey('state')->and($history->toArray())->not->toHaveKeys(['snapshot', 'reason'])
        ->and(json_encode($result, JSON_THROW_ON_ERROR))->not->toContain('-1.9441000', '30.0619000', 'synthetic:reviewed');
    $this->assertDatabaseCount('audit_location_versions', 1);
    expect(verifySyntheticAuditLocation($staff, $party)['code'])->toBe('VERSION_CONFLICT');
    expect(fn () => verifySyntheticAuditLocation($staff, $party, revision: 1, request: $key))->toThrow(CommandRejection::class, 'IDEMPOTENCY_CONFLICT');
});

it('invalidates same-second moves and never restores old coordinates on an operation replay', function (): void {
    $fixture = AuditorFixture::make();
    $staff = $fixture['staff']->id;
    $party = $fixture['party']->id;
    $request = (string) Str::uuid();
    $original = verifySyntheticAuditLocation($staff, $party, request: $request);
    $moved = app(MarkAuditLocationMoved::class);
    $time = now('UTC')->format('Y-m-d\TH:i:s\Z');
    expect($moved->handle($staff, 'office', $party, 1, $time, 'Office moved.', (string) Str::uuid())['code'])->toBe('AUDIT_LOCATION_INVALIDATED')
        ->and(verifySyntheticAuditLocation($staff, $party, request: $request))->toBe($original);
    $state = app(GetAuditLocation::class)->handle($staff, 'office', $party)['state'];
    expect($state['point'])->toBeNull()->and($state['verified_at'])->toBeNull()->and($state['uncertainty_m'])->toBeNull()->and($state['moved_at'])->toBe($time);
    expect($moved->handle($staff, 'office', $party, 2, now('UTC')->subSecond()->format('Y-m-d\TH:i:s\Z'), 'Late move event.', (string) Str::uuid())['code'])->toBe('AUDIT_LOCATION_MOVE_STALE');
    expect(app(VerifyAuditLocation::class)->handle($staff, 'office', $party, 2, '-1', '30', 20,
        now('UTC')->subSecond()->format('Y-m-d\TH:i:s\Z'), 'synthetic:old', 'Old verification.', (string) Str::uuid())['code'])->toBe('AUDIT_LOCATION_REVIEW_REQUIRED');
    expect(verifySyntheticAuditLocation($staff, $party, revision: 2)['code'])->toBe('AUDIT_LOCATION_REVIEW_REQUIRED');
    $this->travel(1)->seconds();
    expect(verifySyntheticAuditLocation($staff, $party, revision: 2)['revision'])->toBe(3);
    expect(AuditLocationVersion::query()->where('revision', 1)->firstOrFail()->snapshot['state']['point'])->not->toBeNull();
    $this->assertDatabaseCount('audit_location_versions', 3);
});

it('requires current staff permission and verified person authority on office writes and retries', function (): void {
    $fixture = AuditorFixture::make();
    $staff = $fixture['staff'];
    $party = $fixture['party'];
    $request = (string) Str::uuid();
    verifySyntheticAuditLocation($staff->id, $party->id, request: $request);
    $party->forceFill(['verified_at' => null])->save();
    expect(fn () => verifySyntheticAuditLocation($staff->id, $party->id, request: $request))->toThrow(IdentityViolation::class, 'PARTY_AUTHORITY_REQUIRED');
    expect(app(MarkAuditLocationMoved::class)->handle($staff->id, 'office', $party->id, 1, now('UTC')->format('Y-m-d\TH:i:s\Z'), 'Invalidate moved office.', (string) Str::uuid())['code'])->toBe('AUDIT_LOCATION_INVALIDATED');
    app(ConfigureStaffAccess::class)->handle($staff->id, true, 'Read-only staff now.', (string) Str::uuid(), ['analyst']);
    expect(fn () => app(GetAuditLocation::class)->handle($staff->id, 'office', $party->id))->toThrow(IdentityViolation::class, 'STAFF_PERMISSION_REQUIRED');
    expect(fn () => verifySyntheticAuditLocation($staff->id, $party->id, request: $request))->toThrow(IdentityViolation::class, 'STAFF_PERMISSION_REQUIRED');
    expect(fn () => verifySyntheticAuditLocation($fixture['user']->id, $party->id))->toThrow(IdentityViolation::class);
});

it('uses current Business entity and all-person verification for premises', function (string $kind, int $people): void {
    $fixture = BusinessAuthorityFixture::make($kind, $people);
    $configured = BusinessAuthorityFixture::configure($fixture);
    $business = $configured['data']['business']['id'];
    $staff = $fixture['staff']->id;
    expect(verifySyntheticAuditLocation($staff, $business, 'premises')['code'])->toBe('AUDIT_LOCATION_VERIFIED');
    expect(app(GetAuditLocation::class)->handle($staff, 'premises', $business)['revision'])->toBe(1);
    $fixture['people'][0]->forceFill(['verified_at' => null])->save();
    expect(fn () => verifySyntheticAuditLocation($staff, $business, 'premises', 1))->toThrow(IdentityViolation::class, 'PARTY_AUTHORITY_REQUIRED');
    $fixture['terms']['status'] = 'revoked';
    BusinessAuthorityFixture::configure($fixture, 1);
    expect(fn () => verifySyntheticAuditLocation($staff, $business, 'premises', 1))->toThrow(CommandRejection::class, 'MANDATE_REQUIRED');
    expect(app(MarkAuditLocationMoved::class)->handle($staff, 'premises', $business, 1, now('UTC')->format('Y-m-d\TH:i:s\Z'), 'Premises moved.', (string) Str::uuid())['code'])->toBe('AUDIT_LOCATION_INVALIDATED');
})->with([['person', 1], ['organization', 2]]);

it('does not expose absent businesses or authorize staff review from a marketplace role', function (): void {
    $fixture = BusinessAuthorityFixture::make();
    expect(fn () => app(WithBusinessReview::class)->handle($fixture['users'][0]->id, (string) Str::ulid(), false, fn (): bool => true))->toThrow(IdentityViolation::class);
    expect(fn () => app(WithBusinessReview::class)->handle($fixture['staff']->id, (string) Str::ulid(), false, fn (): bool => true))->toThrow(CommandRejection::class, 'BUSINESS_NOT_FOUND');
    $record = BusinessProfile::factory()->create();
    expect(fn () => app(WithBusinessReview::class)->handle($fixture['staff']->id, $record->id, true, fn (): bool => true))->toThrow(CommandRejection::class, 'BUSINESS_NOT_FOUND');
    expect(fn () => app(GetAuditLocation::class)->handle($fixture['staff']->id, 'unrecognized', $fixture['entity']))->toThrow(CommandRejection::class, 'AUDIT_LOCATION_KIND_INVALID');
    expect(app(MarkAuditLocationMoved::class)->handle($fixture['staff']->id, 'office', $fixture['entity'], 0, now('UTC')->format('Y-m-d\TH:i:s\Z'), 'No current office.', (string) Str::uuid())['code'])->toBe('AUDIT_LOCATION_NOT_FOUND');
});

it('records validation failures without creating locations and preserves the failed outcome on retry', function (string $case, string $code): void {
    $fixture = AuditorFixture::make();
    $latitude = '-1';
    $uncertainty = 20;
    $time = now('UTC')->format('Y-m-d\TH:i:s\Z');
    $reference = 'synthetic:location';
    $reason = 'Reviewed synthetic coordinates.';
    switch ($case) {
        case 'invalid coordinates': $latitude = 'NaN';
            break;
        case 'negative uncertainty': $uncertainty = -1;
            break;
        case 'unbounded uncertainty': $uncertainty = 30001;
            break;
        case 'stale verification': $time = now('UTC')->subDays(365)->subSecond()->format('Y-m-d\TH:i:s\Z');
            break;
        case 'future verification': $time = now('UTC')->addSecond()->format('Y-m-d\TH:i:s\Z');
            break;
        case 'invalid time': $time = 'not a timestamp';
            break;
        case 'impossible date': $time = '2026-02-30T10:00:00Z';
            break;
        case 'empty reference': $reference = '  ';
            break;
        case 'long reference': $reference = str_repeat('x', 256);
            break;
        case 'invalid utf8': $reference = "\xFF";
            break;
        case 'control reference': $reference = "ref\u{202E}";
            break;
        case 'long reason': $reason = str_repeat('x', 2001);
            break;
    }
    $request = (string) Str::uuid();
    $action = fn (): array => app(VerifyAuditLocation::class)->handle($fixture['staff']->id, 'office', $fixture['party']->id, 0,
        $latitude, '30', $uncertainty, $time, $reference, $reason, $request);
    if ($case === 'invalid utf8') {
        expect($action)->toThrow(CommandRejection::class, 'CANONICAL_VALUE_INVALID');
    } else {
        $result = $action();
        expect($result['code'])->toBe($code)->and($result['http_status'])->toBe(422)->and($action())->toBe($result);
    }
    $this->assertDatabaseCount('audit_locations', 0);
    $this->assertDatabaseCount('audit_location_versions', 0);
})->with([
    ['invalid coordinates', 'AUDIT_COORDINATES_INVALID'],
    ['negative uncertainty', 'AUDIT_LOCATION_REVIEW_REQUIRED'], ['unbounded uncertainty', 'AUDIT_LOCATION_REVIEW_REQUIRED'], ['stale verification', 'AUDIT_LOCATION_REVIEW_REQUIRED'],
    ['future verification', 'AUDIT_LOCATION_TIME_INVALID'], ['invalid time', 'AUDIT_LOCATION_TIME_INVALID'], ['impossible date', 'AUDIT_LOCATION_TIME_INVALID'],
    ['empty reference', 'AUDIT_LOCATION_EVIDENCE_REQUIRED'], ['long reference', 'AUDIT_LOCATION_EVIDENCE_REQUIRED'], ['invalid utf8', 'AUDIT_LOCATION_EVIDENCE_REQUIRED'],
    ['control reference', 'AUDIT_LOCATION_EVIDENCE_REQUIRED'], ['long reason', 'AUDIT_LOCATION_EVIDENCE_REQUIRED'],
]);

it('enforces history immutability unique subjects and valid revisions in PostgreSQL', function (): void {
    $history = AuditLocationVersion::factory()->create();
    expect(fn () => DB::transaction(fn () => $history->forceFill(['actor_user_id' => 42])->save()))->toThrow(QueryException::class, 'immutable');
    expect(fn () => DB::transaction(fn () => $history->delete()))->toThrow(QueryException::class, 'immutable');
    expect(fn () => DB::transaction(fn () => AuditLocation::factory()->create(['office_party_id' => null])))->toThrow(QueryException::class, 'audit_location_subject');
    expect(fn () => DB::transaction(fn () => AuditLocation::factory()->create(['revision' => 0])))->toThrow(QueryException::class, 'audit_location_revision');
    expect(fn () => DB::transaction(fn () => AuditLocation::factory()->create(['office_party_id' => $history->snapshot['subject_id']])))->toThrow(QueryException::class);
    expect(fn () => DB::transaction(fn () => AuditLocationVersion::factory()->create(['revision' => 0])))->toThrow(QueryException::class, 'audit_location_version_revision');
});

it('rolls back location history and receipt together if persistence fails', function (): void {
    $fixture = AuditorFixture::make();
    $count = CommandOperation::query()->count();
    $event = 'eloquent.creating: '.AuditLocationVersion::class;
    Event::listen($event, fn () => throw new RuntimeException('Synthetic location failure'));
    try {
        expect(fn () => verifySyntheticAuditLocation($fixture['staff']->id, $fixture['party']->id))->toThrow(RuntimeException::class, 'Synthetic location failure');
    } finally {
        Event::forget($event);
    }
    $this->assertDatabaseCount('audit_locations', 0);
    $this->assertDatabaseCount('audit_location_versions', 0);
    $this->assertDatabaseCount('command_operations', $count);
});

it('reverses and reapplies location schema without losing other aggregates', function (): void {
    $migration = require database_path('migrations/2026_09_24_103630_create_audit_locations_and_history.php');
    $migration->down();
    expect(Schema::hasTable('audit_locations'))->toBeFalse();
    $migration->up();
    expect(Schema::hasTable('audit_location_versions'))->toBeTrue();
    expect(AuditLocationVersion::factory()->create()->snapshot['revision'])->toBe(1);
});
