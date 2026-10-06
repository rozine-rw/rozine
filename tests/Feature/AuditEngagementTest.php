<?php

declare(strict_types=1);

use App\Application\Auditor\AcceptAuditEngagementTerms;
use App\Application\Auditor\Contracts\AuditAssignmentStore;
use App\Application\Auditor\Contracts\AuditEngagementStore;
use App\Application\Auditor\FindAuditEngagementOperation;
use App\Application\Auditor\GetAuditEngagementTerms;
use App\Application\Auditor\GetAuditOperationsCase;
use App\Application\Auditor\RecordAuditEngagementTerms;
use App\Application\Auditor\ResolveAuditAssignment;
use App\Application\Auditor\WithAcceptedAuditAssignment;
use App\Application\Identity\ConfigureStaffAccess;
use App\Application\Identity\SelectActiveRole;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Domain\Auditor\AuditEngagementDocuments;
use App\Domain\Identity\IdentityViolation;
use App\Domain\Operations\CommandRejection;
use App\Models\AuditEngagementAcceptance;
use App\Models\AuditEngagementRelease;
use App\Models\CommandOperation;
use App\Models\RoleMembership;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\Support\AuditAssignmentFixture;
use Tests\Support\AuditEngagementFixture;
use Tests\Support\AuditorFixture;
use Tests\Support\ConsentFixture;

it('does not manufacture terms or acceptance when an Auditor reads an unconfigured catalog', function (): void {
    $actor = AuditorFixture::make();
    expect(app(GetAuditEngagementTerms::class)->handle($actor['user']->id, 1))->toBe(['release' => null, 'acceptance' => null]);
    $this->assertDatabaseCount('audit_engagement_releases', 0);
    $this->assertDatabaseCount('audit_engagement_acceptances', 0);
    $result = app(AcceptAuditEngagementTerms::class)->handle($actor['user']->id, 1, (string) Str::ulid(), 1, str_repeat('a', 64), true, (string) Str::uuid());
    expect($result['code'])->toBe('AUDIT_ENGAGEMENT_TERMS_REQUIRED');
});

it('retains full immutable documents and requires an explicit acceptance of their current pins', function (): void {
    $actor = AuditorFixture::make();
    $release = AuditEngagementFixture::release($actor['staff']);
    $page = app(GetAuditEngagementTerms::class)->handle($actor['user']->id, 1);
    expect($page['release']['documents']['master_services']['body'])->toBe(AuditEngagementFixture::documents()['master_services']['body'])
        ->and($page['acceptance'])->toBeNull();
    $receipt = AuditEngagementFixture::accept($actor['user'], $release);
    $acceptance = AuditEngagementAcceptance::query()->firstOrFail();
    expect($receipt['code'])->toBe('AUDIT_ENGAGEMENT_ACCEPTED')
        ->and($receipt['data']['acceptance']['release_sha256'])->toBe($release->sha256)
        ->and($acceptance->party_id)->toBe($actor['party']->id)
        ->and($acceptance->payload['actor_user_id'])->toBe($actor['user']->id)
        ->and($acceptance->payload['accepted'])->toBeTrue()
        ->and($acceptance->sha256)->toBe(hash('sha256', app(CanonicalJson::class)->encode($acceptance->payload)))
        ->and(app(GetAuditEngagementTerms::class)->handle($actor['user']->id, 1)['acceptance'])->toEqual($receipt['data']['acceptance']);
    expect(DB::table('audit_engagement_releases')->value('documents'))->not->toContain('SYNTHETIC ALPHA ONLY')
        ->and(DB::table('audit_engagement_acceptances')->value('payload'))->not->toContain($actor['party']->id)
        ->and($release->toArray())->not->toHaveKeys(['documents', 'approval_reference', 'reason'])
        ->and($acceptance->toArray())->not->toHaveKey('payload');
});

it('journals stale pins or an unchecked acceptance without creating a signature', function (string $case, string $code): void {
    $actor = AuditorFixture::make();
    $release = AuditEngagementFixture::release($actor['staff']);
    $result = app(AcceptAuditEngagementTerms::class)->handle($actor['user']->id, 1,
        $case === 'id' ? (string) Str::ulid() : $release->id, $case === 'revision' ? 0 : 1,
        $case === 'hash' ? str_repeat('a', 64) : $release->sha256, $case !== 'unchecked', (string) Str::uuid());
    expect($result['status'])->toBe('rejected')->and($result['code'])->toBe($code)->and($result['revision'])->toBe(1);
    $this->assertDatabaseCount('audit_engagement_acceptances', 0);
})->with([['id', 'AUDIT_ENGAGEMENT_VERSION_CONFLICT'], ['revision', 'AUDIT_ENGAGEMENT_VERSION_CONFLICT'],
    ['hash', 'AUDIT_ENGAGEMENT_VERSION_CONFLICT'], ['unchecked', 'AUDIT_ENGAGEMENT_ACCEPTANCE_REQUIRED']]);

it('deduplicates acceptance across requests and logins of the same canonical Party', function (): void {
    $actor = AuditorFixture::make();
    $release = AuditEngagementFixture::release($actor['staff']);
    $request = (string) Str::uuid();
    $first = AuditEngagementFixture::accept($actor['user'], $release, $request);
    expect(AuditEngagementFixture::accept($actor['user'], $release, $request))->toBe($first)
        ->and(AuditEngagementFixture::accept($actor['user'], $release)['data'])->toBe($first['data']);
    $otherLogin = User::factory()->withTwoFactor()->for($actor['party'])->create();
    app(SelectActiveRole::class)->handle($otherLogin->id, 'auditor', 0, (string) Str::uuid());
    expect(AuditEngagementFixture::accept($otherLogin, $release)['data'])->toBe($first['data']);
    $this->assertDatabaseCount('audit_engagement_acceptances', 1);
    expect(fn () => app(AcceptAuditEngagementTerms::class)->handle($actor['user']->id, 1, $release->id, 1, $release->sha256, false, $request))
        ->toThrow(CommandRejection::class, 'IDEMPOTENCY_CONFLICT');
});

it('makes changed or withdrawn terms require new acceptance while retaining historical receipts', function (): void {
    $actor = AuditorFixture::make();
    $release = AuditEngagementFixture::release($actor['staff']);
    $request = (string) Str::uuid();
    $receipt = AuditEngagementFixture::accept($actor['user'], $release, $request);
    $store = app(AuditEngagementStore::class);
    expect($store->withCurrentAcceptances([$actor['party']->id], fn (array $records): array => $records))
        ->toEqual([$actor['party']->id => $receipt['data']['acceptance']]);
    AuditEngagementFixture::release($actor['staff'], 1, 'withdrawn');
    expect(app(GetAuditEngagementTerms::class)->handle($actor['user']->id, 1))->toBe(['release' => null, 'acceptance' => null])
        ->and($store->withCurrentAcceptances([$actor['party']->id], fn (array $records): array => $records))->toBe([])
        ->and(AuditEngagementFixture::accept($actor['user'], $release, $request))->toBe($receipt)
        ->and(app(FindAuditEngagementOperation::class)->handle($actor['user']->id, 1, $request))->toBe($receipt);
    $next = AuditEngagementFixture::release($actor['staff'], 2);
    expect(app(GetAuditEngagementTerms::class)->handle($actor['user']->id, 1)['acceptance'])->toBeNull()
        ->and(AuditEngagementFixture::accept($actor['user'], $release)['code'])->toBe('AUDIT_ENGAGEMENT_VERSION_CONFLICT');
    AuditEngagementFixture::accept($actor['user'], $next);
    expect($release->refresh()->documents)->toBe((new AuditEngagementDocuments)->normalize(AuditEngagementFixture::documents()));
    $this->assertDatabaseCount('audit_engagement_acceptances', 2);
});

it('never exposes another Party acceptance or receipt', function (): void {
    $actor = AuditorFixture::make();
    $other = AuditorFixture::make();
    $release = AuditEngagementFixture::release($actor['staff']);
    $request = (string) Str::uuid();
    AuditEngagementFixture::accept($actor['user'], $release, $request);
    expect(app(GetAuditEngagementTerms::class)->handle($other['user']->id, 1)['acceptance'])->toBeNull()
        ->and(fn () => app(FindAuditEngagementOperation::class)->handle($other['user']->id, 1, $request))->toThrow(CommandRejection::class, 'OPERATION_NOT_FOUND')
        ->and(app(AuditEngagementStore::class)->withCurrentAcceptances([$other['party']->id], fn (array $records): array => $records))->toBe([]);
});

it('rechecks current role context MFA and revocation before terms acceptance or receipt replay', function (string $case): void {
    $actor = AuditorFixture::make();
    $release = AuditEngagementFixture::release($actor['staff']);
    $request = (string) Str::uuid();
    AuditEngagementFixture::accept($actor['user'], $release, $request);
    if ($case === 'mfa') {
        $actor['user']->forceFill(['two_factor_confirmed_at' => null])->save();
    } elseif ($case === 'revoked') {
        RoleMembership::query()->where('party_id', $actor['party']->id)->update(['status' => 'revoked']);
    }
    $context = $case === 'context' ? 0 : 1;
    expect(fn () => app(GetAuditEngagementTerms::class)->handle($actor['user']->id, $context))->toThrow(IdentityViolation::class)
        ->and(fn () => app(AcceptAuditEngagementTerms::class)->handle($actor['user']->id, $context, $release->id, 1, $release->sha256, true, $request))->toThrow(IdentityViolation::class)
        ->and(fn () => app(FindAuditEngagementOperation::class)->handle($actor['user']->id, $context, $request))->toThrow(IdentityViolation::class);
})->with(['mfa', 'revoked', 'context']);

it('only allows authorized compliance staff to release retained terms', function (string $role, bool $allowed): void {
    $staff = ConsentFixture::staff($role);
    if ($allowed) {
        expect(AuditEngagementFixture::release($staff)->revision)->toBe(1);
    } else {
        expect(fn () => AuditEngagementFixture::release($staff))->toThrow(IdentityViolation::class, 'STAFF_PERMISSION_REQUIRED');
    }
})->with([['compliance', true], ['superadmin', true], ['analyst', false], ['approver', false], ['treasury', false]]);

it('journals catalog revision conflicts and rejects reusing an immutable document version', function (): void {
    $staff = ConsentFixture::staff();
    $request = (string) Str::uuid();
    $action = app(RecordAuditEngagementTerms::class);
    $record = fn (int $revision, string $id): array => $action->handle($staff->id, $revision, 'active', 'synthetic-v1', AuditEngagementFixture::documents(), true, 'fixture:approval', 'Retained terms.', $id);
    $first = $record(0, $request);
    expect($record(0, $request))->toBe($first)
        ->and($record(0, (string) Str::uuid())['code'])->toBe('VERSION_CONFLICT')
        ->and($record(1, (string) Str::uuid())['code'])->toBe('AUDIT_ENGAGEMENT_VERSION_CONFLICT');
    app(ConfigureStaffAccess::class)->handle($staff->id, false, 'Remove terms publisher.', (string) Str::uuid());
    expect(fn () => $record(0, $request))->toThrow(IdentityViolation::class, 'STAFF_ACCESS_REQUIRED');
    $this->assertDatabaseCount('audit_engagement_releases', 1);
});

it('records malformed release denials without retaining an incomplete contract', function (string $case): void {
    $staff = ConsentFixture::staff();
    $result = app(RecordAuditEngagementTerms::class)->handle($staff->id, 0, $case === 'status' ? 'ready' : ($case === 'withdrawal' ? 'withdrawn' : 'active'),
        $case === 'version' ? '' : 'synthetic-v1', $case === 'documents' ? [] : AuditEngagementFixture::documents(), true,
        $case === 'reference' ? '' : 'fixture:approval', $case === 'reason' ? '' : 'Retain exact terms.', (string) Str::uuid());
    expect($result['code'])->toBe('AUDIT_ENGAGEMENT_INPUT_INVALID')->and($result['status'])->toBe('rejected');
    $this->assertDatabaseCount('audit_engagement_releases', 0);
})->with(['status', 'withdrawal', 'version', 'documents', 'reference', 'reason']);

it('fails closed on synthetic terms outside isolated environments', function (): void {
    $actor = AuditorFixture::make();
    $release = AuditEngagementFixture::release($actor['staff']);
    AuditEngagementFixture::accept($actor['user'], $release);
    $this->app->instance('env', 'production');
    expect(app(GetAuditEngagementTerms::class)->handle($actor['user']->id, 1))->toBe(['release' => null, 'acceptance' => null])
        ->and(AuditEngagementFixture::accept($actor['user'], $release)['code'])->toBe('AUDIT_ENGAGEMENT_TERMS_REQUIRED')
        ->and(fn () => AuditEngagementFixture::release($actor['staff'], 1))->toThrow(CommandRejection::class, 'SYNTHETIC_ENGAGEMENT_DENIED');
});

it('can serve a non-synthetic approved catalog without allowing synthetic seeding', function (): void {
    $actor = AuditorFixture::make();
    AuditEngagementRelease::factory()->create(['synthetic' => false]);
    $this->app->instance('env', 'production');
    expect(app(GetAuditEngagementTerms::class)->handle($actor['user']->id, 1)['release']['synthetic'])->toBeFalse();
});

it('protects every retained engagement record from updates and deletes', function (string $record, string $operation): void {
    $model = $record === 'release' ? AuditEngagementRelease::factory()->create() : AuditEngagementAcceptance::factory()->create();
    expect(fn () => DB::transaction(fn (): mixed => $operation === 'update' ? $model->forceFill(['sha256' => str_repeat('a', 64)])->save() : $model->delete()))
        ->toThrow(QueryException::class, 'Audit engagement terms and acceptance history are immutable');
})->with(['release', 'acceptance'])->with(['update', 'delete']);

it('rejects acceptance records with mismatched release pins at the database boundary', function (): void {
    expect(fn () => DB::transaction(fn () => AuditEngagementAcceptance::factory()->create(['release_sha256' => str_repeat('a', 64)])))
        ->toThrow(QueryException::class, 'audit_engagement_acceptance_release');
});

it('treats a retained previous procedure as unavailable without disabling offer expiry or Operations', function (): void {
    $fixture = AuditAssignmentFixture::make(1);
    $assignment = AuditAssignmentFixture::request($fixture);
    $actor = $fixture['partners'][0]['user'];
    $release = AuditEngagementRelease::factory()->create(['revision' => 2, 'procedure_version' => 'MVP-AUP-0']);
    expect(app(GetAuditEngagementTerms::class)->handle($actor->id, 1))->toBe(['release' => null, 'acceptance' => null])
        ->and(AuditEngagementFixture::accept($actor, $release)['code'])->toBe('AUDIT_ENGAGEMENT_TERMS_REQUIRED');
    $this->travelTo(CarbonImmutable::parse($assignment->state['accept_by']));
    expect(app(AuditAssignmentStore::class)->advanceDue(25))->toBe(1)
        ->and($assignment->refresh()->status)->toBe('operations');
    expect(app(GetAuditOperationsCase::class)->handle($fixture['staff']->id, $assignment->id))
        ->toMatchArray(['id' => $assignment->id, 'status' => 'operations']);
    $other = AuditAssignmentFixture::make(0);
    expect(AuditAssignmentFixture::request($other)->status)->toBe('operations');
});

it('detects catalog and acceptance integrity failures before returning any agreement facts', function (string $record): void {
    $actor = AuditorFixture::make();
    $release = AuditEngagementRelease::factory()->create(match ($record) {
        'release' => ['sha256' => str_repeat('a', 64)],
        'superseded_release' => ['sha256' => str_repeat('a', 64), 'procedure_version' => 'MVP-AUP-0'],
        default => [],
    });
    if ($record === 'acceptance') {
        AuditEngagementAcceptance::factory()->create(['audit_engagement_release_id' => $release->id, 'party_id' => $actor['party']->id, 'sha256' => str_repeat('a', 64)]);
    }
    expect(fn () => app(GetAuditEngagementTerms::class)->handle($actor['user']->id, 1))
        ->toThrow(RuntimeException::class, $record === 'acceptance' ? 'AUDIT_ENGAGEMENT_ACCEPTANCE_INTEGRITY_FAILED' : 'AUDIT_ENGAGEMENT_INTEGRITY_FAILED');
})->with(['release', 'superseded_release', 'acceptance']);

it('rolls back protected effects when agreement-dependent work fails', function (): void {
    $actor = AuditorFixture::make();
    AuditEngagementFixture::ready($actor['staff'], $actor['user']);
    expect(fn () => app(AuditEngagementStore::class)->withCurrentAcceptances([$actor['party']->id], function () use ($actor): never {
        $actor['user']->forceFill(['name' => 'Must roll back'])->save();
        throw new RuntimeException('Protected procedure failed.');
    }))->toThrow(RuntimeException::class, 'Protected procedure failed.');
    expect($actor['user']->refresh()->name)->not->toBe('Must roll back');
});

it('excludes unsigned terms from dispatch and offer acceptance until the current terms are accepted', function (): void {
    $fixture = AuditAssignmentFixture::make(1);
    $release = AuditEngagementFixture::release($fixture['staff'], 1);
    $assignment = AuditAssignmentFixture::request($fixture);
    expect($assignment->status)->toBe('operations')->and($assignment->party_id)->toBeNull();
    AuditEngagementFixture::accept($fixture['partners'][0]['user'], $release);
    app(ResolveAuditAssignment::class)->handle($fixture['staff']->id, $assignment->id, $assignment->revision, 'redispatch', 'Current terms accepted.', (string) Str::uuid());
    $assignment->refresh();
    expect($assignment->status)->toBe('offered');
    $replacement = AuditEngagementFixture::release($fixture['staff'], 2);
    expect(AuditAssignmentFixture::respond($fixture['partners'][0]['user'], $assignment)['code'])->toBe('AUDIT_ENGAGEMENT_ACCEPTANCE_REQUIRED');
    AuditEngagementFixture::accept($fixture['partners'][0]['user'], $replacement);
    expect(AuditAssignmentFixture::respond($fixture['partners'][0]['user'], $assignment)['code'])->toBe('ASSIGNMENT_ACCEPTED');
});

it('gates protected evidence by current terms without changing assignment revision or invalidating historical source facts', function (): void {
    $fixture = AuditAssignmentFixture::make(1);
    $sources = AuditAssignmentFixture::statements($fixture);
    $assignment = AuditAssignmentFixture::request($fixture);
    $actor = $fixture['partners'][0]['user'];
    AuditAssignmentFixture::respond($actor, $assignment);
    $revision = $assignment->refresh()->revision;
    $protected = app(WithAcceptedAuditAssignment::class);
    $original = $protected->handle($actor->id, 1, $assignment->id, fn (array $context): array => $context);
    AuditAssignmentFixture::verifyStatements($fixture, $assignment, $sources['transcription_id']);
    $release = AuditEngagementFixture::release($fixture['staff'], 1);
    expect(fn () => $protected->handle($actor->id, 1, $assignment->id, fn (): bool => true))->toThrow(CommandRejection::class, 'AUDIT_ENGAGEMENT_ACCEPTANCE_REQUIRED')
        ->and(app(AuditAssignmentStore::class)->retainsVerification($original))->toBeTrue();
    AuditEngagementFixture::accept($actor, $release);
    $current = $protected->handle($actor->id, 1, $assignment->id, fn (array $context): array => $context);
    expect($current['engagement']['release_id'])->toBe($release->id)->and($current['revision'])->toBe($revision)
        ->and($assignment->refresh()->revision)->toBe($revision)->and($original['engagement']['release_id'])->not->toBe($release->id);
});

it('refuses a receipt from a different journal target type', function (): void {
    $actor = AuditorFixture::make();
    $request = (string) Str::uuid();
    CommandOperation::factory()->create(['actor_key' => 'party:'.$actor['party']->id, 'command' => 'audit.engagement.accept',
        'request_id' => $request, 'target_type' => 'application', 'target_id' => (string) Str::ulid()]);
    expect(fn () => app(FindAuditEngagementOperation::class)->handle($actor['user']->id, 1, $request))->toThrow(CommandRejection::class, 'OPERATION_NOT_FOUND');
});

it('refuses destructive rollback of retained engagement history', function (): void {
    AuditEngagementAcceptance::factory()->create();
    $migration = require database_path('migrations/2026_09_25_070105_create_audit_engagement_releases_and_acceptances.php');
    expect(fn () => $migration->down())->toThrow(RuntimeException::class, 'Existing audit engagement history requires a forward migration.');
    $this->assertDatabaseCount('audit_engagement_releases', 1);
    $this->assertDatabaseCount('audit_engagement_acceptances', 1);
});

it('can reverse an unused engagement schema without deleting other history', function (): void {
    $migration = require database_path('migrations/2026_09_25_070105_create_audit_engagement_releases_and_acceptances.php');
    $sourcePins = require database_path('migrations/2026_09_25_082804_enforce_audit_engagement_source_pins.php');
    $sourcePins->down();
    $migration->down();
    expect(Schema::hasTable('audit_engagement_releases'))->toBeFalse();
    $migration->up();
    $sourcePins->up();
    expect(Schema::hasTable('audit_engagement_releases'))->toBeTrue();
});

it('rolls acceptance and its journal back when the retained acceptance cannot be committed', function (): void {
    $actor = AuditorFixture::make();
    $release = AuditEngagementFixture::release($actor['staff']);
    $count = CommandOperation::query()->count();
    $event = 'eloquent.created: '.AuditEngagementAcceptance::class;
    Event::listen($event, function (): never {
        throw new RuntimeException('Acceptance retention failed.');
    });
    try {
        expect(fn () => AuditEngagementFixture::accept($actor['user'], $release))->toThrow(RuntimeException::class, 'Acceptance retention failed.');
    } finally {
        Event::forget($event);
    }
    $this->assertDatabaseCount('audit_engagement_acceptances', 0);
    $this->assertDatabaseCount('command_operations', $count);
});
