<?php

declare(strict_types=1);

use App\Application\Auditor\GetAuditSourceFacts;
use App\Application\Auditor\RecordIsolatedAuditSourceFacts;
use App\Application\Auditor\WithAcceptedAuditAssignment;
use App\Application\Identity\ConfigureStaffAccess;
use App\Domain\Auditor\AuditEngagementDocuments;
use App\Domain\Identity\IdentityViolation;
use App\Domain\Operations\CommandRejection;
use App\Models\AuditSourceSnapshot;
use App\Models\CommandOperation;
use App\Models\RoleMembership;
use Illuminate\Database\QueryException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\Support\AuditAssignmentFixture;
use Tests\Support\AuditSourceFactsFixture as Fixture;

/**
 * @param  array<string, mixed>  $fixture
 * @return array<string, mixed>|null
 */
function readAuditSourceFacts(array $fixture, ?int $userId = null): ?array
{
    return app(GetAuditSourceFacts::class)->handle($userId ?? $fixture['partner']['user']->id, 1, $fixture['assignment']->id);
}

beforeEach(function (): void {
    $this->freezeSecond();
});

it('records encrypted assignment-bound facts once and returns them only through accepted authority', function (): void {
    $fixture = Fixture::accepted();
    $staff = $fixture['audit']['staff'];
    $request = (string) Str::uuid();
    $result = Fixture::record($staff, $fixture['assignment'], requestId: $request);
    $record = AuditSourceSnapshot::query()->firstOrFail();
    expect($result['code'])->toBe('AUDIT_SOURCE_FACTS_RECORDED')->and($result['revision'])->toBe(1)
        ->and($result['data']['audit_source'])->toEqual(['id' => $record->id, 'revision' => 1, 'status' => 'available',
            'source_kind' => 'isolated_synthetic', 'sha256' => $record->sha256, 'assignment_id' => $fixture['assignment']->id,
            'assignment_revision' => $fixture['assignment']->revision])
        ->and(Fixture::record($staff, $fixture['assignment'], requestId: $request))->toBe($result);
    $facts = Fixture::facts();
    expect(readAuditSourceFacts($fixture))->toBe(['source' => ['id' => $record->id, 'revision' => 1, 'sha256' => $record->sha256,
        'kind' => 'isolated_synthetic', 'reference' => 'synthetic:c2-stock-declaration', 'procedure_version' => AuditEngagementDocuments::PROCEDURE,
        'assignment_id' => $fixture['assignment']->id, 'assignment_revision' => $fixture['assignment']->revision], ...$facts])
        ->and($record->party_id)->toBe($fixture['partner']['party']->id)
        ->and($record->business_id)->toBe($fixture['audit']['business']);
    $journal = CommandOperation::query()->where('command', 'audit.source.fixture')->firstOrFail();
    expect(json_encode($journal->toArray(), JSON_THROW_ON_ERROR))->not->toContain('38000000', 'c2-stock-declaration', 'Sealed crates', 'storefront');
    expect(fn () => Fixture::record($staff, $fixture['assignment'], 1, $request))->toThrow(CommandRejection::class, 'IDEMPOTENCY_CONFLICT');
    $this->assertDatabaseCount('audit_source_snapshots', 1);
});

it('encrypts facts, references and reasons at rest and hides them from model serialization', function (): void {
    $fixture = Fixture::accepted();
    Fixture::record($fixture['audit']['staff'], $fixture['assignment']);
    $record = AuditSourceSnapshot::query()->firstOrFail();
    $raw = (array) DB::table('audit_source_snapshots')->where('id', $record->id)->first();
    foreach (['facts', 'source_reference', 'reason'] as $field) {
        expect($record->toArray())->not->toHaveKey($field)
            ->and($raw[$field])->not->toBe($record->getAttribute($field))
            ->and($raw[$field])->not->toContain('synthetic:', '38000000', 'Sealed crates', 'Isolated C2');
    }
});

it('never turns missing, empty or withdrawn facts into zero and keeps each source revision', function (): void {
    $fixture = Fixture::accepted();
    $staff = $fixture['audit']['staff'];
    expect(readAuditSourceFacts($fixture))->toBeNull();
    Fixture::record($staff, $fixture['assignment'], facts: Fixture::empty());
    $empty = readAuditSourceFacts($fixture);
    expect(Arr::except($empty ?? [], 'source'))->toBe(Fixture::empty())->and($empty['source']['revision'] ?? null)->toBe(1);
    $withdrawal = app(RecordIsolatedAuditSourceFacts::class)->handle($staff->id, $fixture['assignment']->id, $fixture['assignment']->revision, 1,
        null, 'synthetic:withdrawal', 'Withdraw inaccurate source facts.', (string) Str::uuid());
    expect($withdrawal['revision'])->toBe(2)->and($withdrawal['data']['audit_source']['status'])->toBe('withdrawn')
        ->and(readAuditSourceFacts($fixture))->toBeNull()
        ->and(AuditSourceSnapshot::query()->where('revision', 1)->firstOrFail()->facts)->toBe(Fixture::empty());
    $current = Fixture::record($staff, $fixture['assignment'], 2);
    expect($current['revision'])->toBe(3)->and(readAuditSourceFacts($fixture)['declared_stock_rwf'])->toBe('38000000');
    $stale = Fixture::record($staff, $fixture['assignment'], 2);
    expect($stale['status'])->toBe('rejected')->and($stale['code'])->toBe('VERSION_CONFLICT')->and($stale['revision'])->toBe(3);
    $this->assertDatabaseCount('audit_source_snapshots', 3);
});

it('rechecks staff permission, MFA and enablement even for identical retries without exposing assignments', function (): void {
    $fixture = Fixture::accepted();
    $staff = $fixture['audit']['staff'];
    $request = (string) Str::uuid();
    Fixture::record($staff, $fixture['assignment'], requestId: $request);
    $unknown = $fixture['assignment']->replicate();
    $unknown->id = (string) Str::ulid();
    app(ConfigureStaffAccess::class)->handle($staff->id, true, 'Read-only staff.', (string) Str::uuid(), ['analyst']);
    foreach ([$fixture['assignment'], $unknown] as $assignment) {
        expect(fn () => Fixture::record($staff, $assignment, requestId: $request))->toThrow(IdentityViolation::class, 'STAFF_PERMISSION_REQUIRED');
    }
    app(ConfigureStaffAccess::class)->handle($staff->id, true, 'Restore verification permission.', (string) Str::uuid(), ['compliance']);
    $staff->forceFill(['two_factor_confirmed_at' => null])->save();
    expect(fn () => Fixture::record($staff, $fixture['assignment'], requestId: $request))->toThrow(IdentityViolation::class, 'STAFF_ACCESS_REQUIRED');
    $staff->forceFill(['two_factor_confirmed_at' => now()])->save();
    app(ConfigureStaffAccess::class)->handle($staff->id, false, 'Withdraw staff access.', (string) Str::uuid());
    expect(fn () => Fixture::record($staff, $fixture['assignment'], requestId: $request))->toThrow(IdentityViolation::class, 'STAFF_ACCESS_REQUIRED')
        ->and(fn () => Fixture::record($fixture['partner']['user'], $fixture['assignment']))->toThrow(IdentityViolation::class, 'STAFF_ACCESS_REQUIRED');
    $this->assertDatabaseCount('audit_source_snapshots', 1);
});

it('binds only the current accepted assignment revision and journals state refusals', function (): void {
    $fixture = Fixture::accepted();
    $staff = $fixture['audit']['staff'];
    $unknown = $fixture['assignment']->replicate();
    $unknown->id = (string) Str::ulid();
    expect(fn () => Fixture::record($staff, $unknown))->toThrow(CommandRejection::class, 'ASSIGNMENT_NOT_FOUND');
    $request = (string) Str::uuid();
    $stale = Fixture::record($staff, $fixture['assignment'], requestId: $request, assignmentRevision: $fixture['assignment']->revision - 1);
    expect($stale['code'])->toBe('ASSIGNMENT_VERSION_CONFLICT')->and($stale['revision'])->toBe($fixture['assignment']->revision)
        ->and(Fixture::record($staff, $fixture['assignment'], requestId: $request, assignmentRevision: $fixture['assignment']->revision - 1))->toBe($stale);
    $offered = AuditAssignmentFixture::make(1);
    $offer = AuditAssignmentFixture::request($offered);
    $refusal = Fixture::record($offered['staff'], $offer);
    expect($refusal['code'])->toBe('ASSIGNMENT_NOT_ACCEPTED')->and($refusal['revision'])->toBe($offer->revision);
    $this->assertDatabaseCount('audit_source_snapshots', 0);
});

it('refuses synthetic facts outside an isolated fixture environment on read, write and replay', function (): void {
    $fixture = Fixture::accepted();
    $request = (string) Str::uuid();
    Fixture::record($fixture['audit']['staff'], $fixture['assignment'], requestId: $request);
    $held = app(WithAcceptedAuditAssignment::class)->handle($fixture['partner']['user']->id, 1, $fixture['assignment']->id, function (array $assignment): ?array {
        $this->app->instance('env', 'production');

        return app(GetAuditSourceFacts::class)->forAccepted($assignment);
    });
    expect($held)->toBeNull()
        ->and(fn () => Fixture::record($fixture['audit']['staff'], $fixture['assignment'], requestId: $request))->toThrow(LogicException::class, 'ISOLATION_SEED_DENIED')
        ->and(fn () => readAuditSourceFacts($fixture))->toThrow(CommandRejection::class, 'AUDIT_ENGAGEMENT_ACCEPTANCE_REQUIRED');
});

it('journals invalid source input without a partial snapshot', function (string $case): void {
    $fixture = Fixture::accepted();
    $revision = 0;
    $reference = 'synthetic:scenario';
    $reason = 'Audit source fixture.';
    $facts = Fixture::facts();
    match ($case) {
        'revision' => $revision = -1,
        'reference' => $reference = 'capture:native-device',
        'empty reference' => $reference = 'synthetic: ',
        'long reference' => $reference .= str_repeat('a', 255),
        'control reference' => $reference .= "\n",
        'empty reason' => $reason = ' ',
        'long reason' => $reason = str_repeat('a', 2001),
        'control reason' => $reason .= "\n",
        'facts' => $facts['photos']['required'][0]['image_url'] = 'https://example.test/storefront.jpg',
        default => throw new LogicException('Unknown invalid audit source scenario.'),
    };
    $key = (string) Str::uuid();
    $record = fn (): array => app(RecordIsolatedAuditSourceFacts::class)->handle($fixture['audit']['staff']->id, $fixture['assignment']->id,
        $fixture['assignment']->revision, $revision, $facts, $reference, $reason, $key);
    $denial = $record();
    expect($denial['status'])->toBe('rejected')->and($denial['http_status'])->toBe(422)
        ->and($denial['code'])->toBe($case === 'facts' ? 'AUDIT_SOURCE_FACTS_INVALID' : 'AUDIT_SOURCE_INVALID')->and($record())->toBe($denial);
    $this->assertDatabaseCount('audit_source_snapshots', 0);
})->with(['revision', 'reference', 'empty reference', 'long reference', 'control reference', 'empty reason', 'long reason', 'control reason', 'facts']);

it('lets neither a replacement Auditor nor the conflicted original inherit bound facts', function (): void {
    $fixture = Fixture::accepted(2);
    $staff = $fixture['audit']['staff'];
    $original = $fixture['partner'];
    Fixture::record($staff, $fixture['assignment']);
    $boundRevision = $fixture['assignment']->revision;
    AuditAssignmentFixture::respond($original['user'], $fixture['assignment'], 'conflict', 'New family tie.', 'family_or_business');
    $assignment = $fixture['assignment']->refresh();
    $replacement = AuditAssignmentFixture::recipient($fixture['audit'], $assignment);
    expect($replacement['party']->id)->not->toBe($original['party']->id)
        ->and(fn () => readAuditSourceFacts($fixture, $replacement['user']->id))->toThrow(CommandRejection::class, 'ASSIGNMENT_NOT_ACCEPTED');
    AuditAssignmentFixture::respond($replacement['user'], $assignment);
    $assignment->refresh();
    expect(readAuditSourceFacts($fixture, $replacement['user']->id))->toBeNull()
        ->and(fn () => readAuditSourceFacts($fixture))->toThrow(CommandRejection::class, 'ASSIGNMENT_NOT_FOUND')
        ->and(Fixture::record($staff, $assignment, assignmentRevision: $boundRevision)['code'])->toBe('ASSIGNMENT_VERSION_CONFLICT');
    $rebound = Fixture::record($staff, $assignment);
    expect($rebound['revision'])->toBe(1)
        ->and(readAuditSourceFacts($fixture, $replacement['user']->id)['source'])->toMatchArray(['id' => $rebound['data']['audit_source']['id'],
            'revision' => 1, 'assignment_revision' => $assignment->revision]);
    $this->assertDatabaseCount('audit_source_snapshots', 2);
});

it('requires current membership, participation and acceptance for every read', function (): void {
    $fixture = Fixture::accepted(2);
    Fixture::record($fixture['audit']['staff'], $fixture['assignment']);
    $other = array_values(array_filter($fixture['audit']['partners'], fn (array $partner): bool => $partner['party']->id !== $fixture['partner']['party']->id))[0];
    expect(fn () => readAuditSourceFacts($fixture, $other['user']->id))->toThrow(CommandRejection::class, 'ASSIGNMENT_NOT_FOUND')
        ->and(fn () => readAuditSourceFacts($fixture, $fixture['audit']['staff']->id))->toThrow(IdentityViolation::class)
        ->and(fn () => readAuditSourceFacts($fixture, $fixture['audit']['authority']['users'][0]->id))->toThrow(CommandRejection::class, 'ASSIGNMENT_NOT_FOUND');
    RoleMembership::query()->where('party_id', $fixture['partner']['party']->id)->update(['status' => 'revoked']);
    expect(fn () => readAuditSourceFacts($fixture))->toThrow(IdentityViolation::class);
});

it('composes inside held accepted-assignment authority without granting access alone', function (): void {
    $fixture = Fixture::accepted();
    Fixture::record($fixture['audit']['staff'], $fixture['assignment']);
    $composed = app(WithAcceptedAuditAssignment::class)->handle($fixture['partner']['user']->id, 1, $fixture['assignment']->id,
        fn (array $assignment): ?array => app(GetAuditSourceFacts::class)->forAccepted($assignment));
    expect($composed)->toBe(readAuditSourceFacts($fixture));
});

it('treats a source for another procedure version as unavailable rather than current', function (): void {
    $fixture = Fixture::accepted();
    AuditSourceSnapshot::factory()->forAssignment($fixture['assignment'])->create(['status' => 'available',
        'facts' => Fixture::facts(), 'procedure_version' => 'MVP-AUP-0']);
    expect(readAuditSourceFacts($fixture))->toBeNull();
});

it('detects tampered digests and bindings before exposing facts', function (string $fault): void {
    $fixture = Fixture::accepted();
    if ($fault === 'digest') {
        AuditSourceSnapshot::factory()->forAssignment($fixture['assignment'])->create(['status' => 'available',
            'facts' => Fixture::facts(), 'sha256' => str_repeat('0', 64)]);
    } else {
        Fixture::record($fixture['audit']['staff'], $fixture['assignment']);
    }
    $event = 'eloquent.retrieved: '.AuditSourceSnapshot::class;
    Event::listen($event, function (AuditSourceSnapshot $record) use ($fault): void {
        match ($fault) {
            'party' => $record->party_id = (string) Str::ulid(),
            'facts' => $record->facts = [...Fixture::facts(), 'declared_stock_rwf' => '1'],
            default => null,
        };
    });
    try {
        expect(fn () => readAuditSourceFacts($fixture))->toThrow(CommandRejection::class, 'AUDIT_SOURCE_FACTS_CORRUPTED');
    } finally {
        Event::forget($event);
    }
})->with(['digest', 'party', 'facts']);

it('enforces append-only history and exact accepted bindings in PostgreSQL', function (): void {
    $fixture = Fixture::accepted();
    Fixture::record($fixture['audit']['staff'], $fixture['assignment']);
    $record = AuditSourceSnapshot::query()->firstOrFail();
    foreach (['update', 'delete'] as $mutation) {
        expect(fn () => DB::transaction(fn () => $mutation === 'update'
            ? DB::table('audit_source_snapshots')->where('id', $record->id)->update(['sha256' => str_repeat('0', 64)])
            : DB::table('audit_source_snapshots')->where('id', $record->id)->delete()))->toThrow(QueryException::class, 'Audit source snapshots are immutable');
    }
    $factory = AuditSourceSnapshot::factory()->forAssignment($fixture['assignment']);
    foreach ([['revision' => 3], ['revision' => 1], ['revision' => 2, 'assignment_revision' => $fixture['assignment']->revision - 1],
        ['revision' => 2, 'party_id' => AuditAssignmentFixture::make(1)['partners'][0]['party']->id]] as $attributes) {
        expect(fn () => DB::transaction(fn () => $factory->create($attributes)))->toThrow(QueryException::class);
    }
    expect(fn () => DB::transaction(fn () => $factory->create(['revision' => 2, 'status' => 'available', 'facts' => null])))
        ->toThrow(QueryException::class, 'audit_source_snapshot_state')
        ->and(fn () => DB::transaction(fn () => $factory->create(['revision' => 2, 'source_kind' => 'native_capture'])))
        ->toThrow(QueryException::class, 'audit_source_snapshot_state');
    $offered = AuditAssignmentFixture::make(1);
    $offer = AuditAssignmentFixture::request($offered);
    expect(fn () => DB::transaction(fn () => AuditSourceSnapshot::factory()->forAssignment($offer)->create()))
        ->toThrow(QueryException::class, 'Audit source facts must bind the current accepted Auditor');
    expect($record->refresh()->sha256)->toBe(AuditSourceSnapshot::query()->firstOrFail()->sha256);
    $this->assertDatabaseCount('audit_source_snapshots', 1);
});

it('rolls back and refuses to drop used source history in the migration', function (): void {
    $migration = require database_path('migrations/2026_09_25_102249_create_audit_source_snapshots_table.php');
    $migration->down();
    expect(Schema::hasTable('audit_source_snapshots'))->toBeFalse();
    $migration->up();
    expect(Schema::hasTable('audit_source_snapshots'))->toBeTrue()
        ->and(collect(DB::select("SELECT tgname FROM pg_trigger WHERE tgrelid = 'audit_source_snapshots'::regclass AND NOT tgisinternal ORDER BY tgname"))
            ->pluck('tgname')->all())->toBe(['audit_source_snapshots_binding', 'audit_source_snapshots_immutable']);
    $fixture = Fixture::accepted();
    Fixture::record($fixture['audit']['staff'], $fixture['assignment']);
    expect(fn () => $migration->down())->toThrow(RuntimeException::class, 'Existing audit source history requires a forward migration.')
        ->and(Schema::hasTable('audit_source_snapshots'))->toBeTrue();
    $this->assertDatabaseCount('audit_source_snapshots', 1);
});
