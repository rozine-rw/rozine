<?php

declare(strict_types=1);

use App\Application\Auditor\AdvanceAuditAssignment;
use App\Application\Auditor\FindAuditResolutionOperation;
use App\Application\Auditor\GetAuditOperationsCase;
use App\Application\Auditor\GetOwnAuditConflict;
use App\Application\Auditor\RequestAuditAssignment;
use App\Application\Auditor\ResolveAuditAssignment;
use App\Application\Identity\ConfigureStaffAccess;
use App\Application\Operations\Contracts\OperationJournal;
use App\Domain\Auditor\AuditEngagementState;
use App\Domain\Identity\IdentityViolation;
use App\Domain\Operations\CommandRejection;
use App\Domain\Operations\OperationResult;
use App\Models\AuditAssignment;
use App\Models\AuditAssignmentVersion;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;
use Tests\Support\AuditAssignmentFixture as Fixture;
use Tests\Support\BusinessAuthorityFixture;

beforeEach(function (): void {
    $this->freezeTime();
});

it('redispatches through current eligibility while preserving the original Flash clock and attempts', function (): void {
    $fixture = Fixture::make(0);
    $assignment = Fixture::request($fixture);
    $original = $assignment->state;
    $action = app(ResolveAuditAssignment::class);
    $pending = $action->handle($fixture['staff']->id, $assignment->id, 1, 'redispatch', 'No partner available yet.', (string) Str::uuid());
    expect($pending['code'])->toBe('ASSIGNMENT_REDISPATCH_PENDING')->and($assignment->refresh()->state['attempt'])->toBe(0);
    $other = Fixture::make(1);
    $partner = $other['partners'][0];
    Fixture::independence($fixture['staff'], $fixture['business'], $partner['party']->id);
    $this->travel(23)->hours();
    $this->travel(30)->minutes();
    $requestId = (string) Str::uuid();
    $receipt = $action->handle($fixture['staff']->id, $assignment->id, 2, 'redispatch', 'Eligibility is now established.', $requestId);
    expect($receipt['code'])->toBe('ASSIGNMENT_REDISPATCHED')->and($receipt['revision'])->toBe(3)
        ->and($assignment->refresh()->state)->toMatchArray(['original_dispatch_at' => $original['original_dispatch_at'],
            'complete_by' => $original['complete_by'], 'accept_by' => $original['complete_by'], 'attempt' => 1, 'tried' => [$partner['party']->id]])
        ->and($action->handle($fixture['staff']->id, $assignment->id, 2, 'redispatch', 'Eligibility is now established.', $requestId))->toBe($receipt)
        ->and(app(FindAuditResolutionOperation::class)->handle($fixture['staff']->id, 'audit.assignment.redispatch', $requestId))->toBe($receipt)
        ->and(AuditAssignmentVersion::query()->where('assignment_id', $assignment->id)->count())->toBe(3)
        ->and(app(GetAuditOperationsCase::class)->handle($fixture['staff']->id, $assignment->id)['allowed_actions'])->toBe([]);
});

it('closes an Operations case without completing an audit or exposing a replacement identity', function (): void {
    $fixture = Fixture::make(1);
    $assignment = Fixture::request($fixture);
    $partner = $fixture['partners'][0];
    Fixture::respond($partner['user'], $assignment, 'conflict', 'Private interest.', 'other');
    $prior = $assignment->refresh()->state;
    $authority = $fixture['authority'];
    $authority['terms']['status'] = 'revoked';
    BusinessAuthorityFixture::configure($authority, 1);
    $this->actingAs($fixture['staff'])->getJson('/admin/audit-assignments/'.$assignment->id)->assertOk()
        ->assertJsonPath('data.case.status', 'operations')->assertJsonPath('data.case.attempt', 1);
    $body = ['expected_revision' => 2, 'reason' => 'No independent partner; case closed.', 'request_id' => (string) Str::uuid()];
    $receipt = $this->postJson('/admin/audit-assignments/'.$assignment->id.'/close', $body)->assertOk()
        ->assertJsonPath('code', 'ASSIGNMENT_CLOSED')->assertJsonPath('allowed_actions', [])->json();
    expect($assignment->refresh()->status)->toBe('closed')->and($assignment->party_id)->toBeNull()->and($assignment->getAttribute('completed_at'))->toBeNull()
        ->and($assignment->state['complete_by'])->toBe($prior['complete_by'])->and($assignment->state['tried'])->toBe($prior['tried'])
        ->and($assignment->state['closed_at'] ?? null)->toBe(now('UTC')->format('Y-m-d\TH:i:s\Z'))
        ->and(app(GetOwnAuditConflict::class)->handle($partner['user']->id, 1, $assignment->id)['conflict']['status'])->toBe('closed');
    $this->getJson($receipt['data']['next']['url'])->assertOk()->assertJsonPath('data.case.status', 'closed')
        ->assertJsonMissingPath('data.case.party_id')->assertJsonMissingPath('data.case.tried');
    Sanctum::actingAs($fixture['staff'], ['staff:audit:read', 'staff:audit:manage']);
    $this->postJson('/api/v1/staff/audit-assignments/'.$assignment->id.'/close', $body)->assertOk()->assertJsonPath('operation_id', $receipt['operation_id']);
    $this->getJson('/api/v1/staff/audit-assignments/operations/'.$body['request_id'].'?command=audit.assignment.close')->assertOk()
        ->assertJsonPath('data.next.url', '/api/v1/staff/audit-assignments/'.$assignment->id)->assertJsonPath('allowed_actions', []);
    $this->getJson('/api/v1/staff/audit-assignments/'.$assignment->id)->assertOk()->assertJsonPath('data.case.closed_at', now('UTC')->format('Y-m-d\TH:i:s\Z'))
        ->assertJsonPath('data.actions.redispatch.url', '/api/v1/staff/audit-assignments/'.$assignment->id.'/redispatch');
    $version = AuditAssignmentVersion::query()->where('assignment_id', $assignment->id)->orderByDesc('revision')->firstOrFail();
    expect($version->command)->toBe('audit.assignment.close')->and($version->getRawOriginal('reason'))->not->toContain($body['reason']);
});

it('does not use Operations to reset exhausted attempts or an elapsed original deadline', function (string $limit): void {
    $fixture = Fixture::make($limit === 'attempts' ? 3 : 0);
    $assignment = Fixture::request($fixture);
    if ($limit === 'attempts') {
        for ($i = 0; $i < 3; $i++) {
            Fixture::respond(Fixture::recipient($fixture, $assignment)['user'], $assignment, 'decline', 'Unavailable.');
            $assignment->refresh();
        }
    } else {
        $this->travel(24)->hours();
    }
    $prior = $assignment->state;
    expect(app(GetAuditOperationsCase::class)->handle($fixture['staff']->id, $assignment->id)['allowed_actions'])->toBe(['audit.assignment.close']);
    $result = app(ResolveAuditAssignment::class)->handle($fixture['staff']->id, $assignment->id, $assignment->revision, 'redispatch', 'Try again.', (string) Str::uuid());
    expect($result['code'])->toBe('AUDIT_DISPATCH_EXHAUSTED')->and($result['http_status'])->toBe(409)->and($assignment->refresh()->state)->toBe($prior);
    app(ResolveAuditAssignment::class)->handle($fixture['staff']->id, $assignment->id, $assignment->revision, 'close', 'Close exhausted engagement.', (string) Str::uuid());
    $closed = $assignment->refresh()->state;
    foreach (['flash', 'routine'] as $kind) {
        $requestId = (string) Str::uuid();
        $request = fn (): array => app(RequestAuditAssignment::class)->handle($fixture['staff']->id, $fixture['business'], $kind, 'Try a new case.', $requestId);
        $refused = $request();
        expect($refused)->toMatchArray(['status' => 'rejected', 'code' => 'AUDIT_ENGAGEMENT_CLOSED', 'http_status' => 409,
            'data' => ['assignment_id' => $assignment->id], 'revision' => $assignment->revision])
            ->and($request())->toBe($refused)->and($assignment->refresh()->state)->toBe($closed);
    }
    expect(AuditAssignment::query()->where('business_id', $fixture['business'])->count())->toBe(1);
})->with(['attempts', 'deadline']);

it('does not disclose or lock a real or missing case before staff permission is established', function (bool $isStaff): void {
    $fixture = Fixture::make(0);
    $assignment = Fixture::request($fixture);
    $user = $isStaff ? $fixture['staff'] : User::factory()->withTwoFactor()->create();
    if ($isStaff) {
        app(ConfigureStaffAccess::class)->handle($user->id, true, 'Read-only analyst.', (string) Str::uuid(), ['analyst']);
    }
    $code = $isStaff ? 'STAFF_PERMISSION_REQUIRED' : 'STAFF_ACCESS_REQUIRED';
    Sanctum::actingAs($user, ['staff:audit:read', 'staff:audit:manage']);
    DB::enableQueryLog();
    DB::flushQueryLog();
    try {
        foreach ([$assignment->id, strtolower((string) Str::ulid())] as $id) {
            $this->getJson('/api/v1/staff/audit-assignments/'.$id)->assertForbidden()->assertExactJson(['message' => $code, 'code' => $code]);
            $this->postJson('/api/v1/staff/audit-assignments/'.$id.'/close', ['expected_revision' => 1, 'reason' => 'Close.', 'request_id' => (string) Str::uuid()])
                ->assertForbidden()->assertExactJson(['message' => $code, 'code' => $code]);
            expect(fn () => app(AdvanceAuditAssignment::class)->handle($user->id, $id, 1, (string) Str::uuid()))->toThrow(IdentityViolation::class, $code);
        }
        expect(fn () => app(RequestAuditAssignment::class)->handle($user->id, 'missing-business', 'flash', 'Request.', (string) Str::uuid()))
            ->toThrow(IdentityViolation::class, $code);
        $this->getJson('/api/v1/staff/audit-assignments/operations/'.Str::uuid().'?command=audit.assignment.close')
            ->assertForbidden()->assertJsonPath('code', $code);
        expect(implode("\n", array_column(DB::getQueryLog(), 'query')))->not->toContain('audit_assignments', 'business_profiles');
    } finally {
        DB::disableQueryLog();
    }
})->with([false, true]);

it('requires both read and manage token abilities before an Operations command can return a case', function (string $ability): void {
    $fixture = Fixture::make(0);
    $assignment = Fixture::request($fixture);
    Sanctum::actingAs($fixture['staff'], [$ability]);
    $read = $this->getJson('/api/v1/staff/audit-assignments/'.$assignment->id);
    $ability === 'staff:audit:read' ? $read->assertOk() : $read->assertForbidden();
    $this->postJson('/api/v1/staff/audit-assignments/'.$assignment->id.'/close', ['expected_revision' => 1, 'reason' => 'Close.', 'request_id' => (string) Str::uuid()])
        ->assertForbidden();
    expect($assignment->refresh()->status)->toBe('operations')->and($assignment->revision)->toBe(1);
})->with(['staff:audit:read', 'staff:audit:manage']);

it('renders withdrawn staff case access as an Inertia denial without stale case facts', function (string $path): void {
    $fixture = Fixture::make(0);
    $assignment = Fixture::request($fixture);
    app(ConfigureStaffAccess::class)->handle($fixture['staff']->id, true, 'Withdraw management.', (string) Str::uuid(), ['analyst']);
    $path = str_replace(['{assignment}', '{request_id}'], [$assignment->id, (string) Str::uuid()], $path);
    $this->actingAs($fixture['staff'])->get($path)->assertForbidden()->assertInertia(fn (Assert $page): Assert => $page
        ->component('identity/access-denied')->where('code', 'STAFF_PERMISSION_REQUIRED')->missing('case')->missing('actions'));
    $this->get($path, ['X-Inertia' => 'true', 'X-Inertia-Version' => (string) Inertia::getVersion()])->assertForbidden()
        ->assertHeader('X-Inertia', 'true')->assertJsonPath('component', 'identity/access-denied');
})->with(['/admin/audit-assignments/{assignment}', '/admin/audit-assignments/operations/{request_id}?command=audit.assignment.close']);

it('requires current Business authority and independence for redispatch and never reuses a tried partner', function (): void {
    $fixture = Fixture::make(1);
    $assignment = Fixture::request($fixture);
    $partner = $fixture['partners'][0];
    Fixture::respond($partner['user'], $assignment, 'decline', 'Unavailable.');
    $assignment->refresh();
    $result = app(ResolveAuditAssignment::class)->handle($fixture['staff']->id, $assignment->id, 2, 'redispatch', 'Eligibility rechecked.', (string) Str::uuid());
    expect($result['code'])->toBe('ASSIGNMENT_REDISPATCH_PENDING')->and($assignment->refresh()->state['tried'])->toBe([$partner['party']->id]);
    $new = Fixture::make(1)['partners'][0];
    Fixture::independence($fixture['staff'], $fixture['business'], $new['party']->id);
    $authority = $fixture['authority'];
    $authority['terms']['status'] = 'revoked';
    BusinessAuthorityFixture::configure($authority, 1);
    $result = app(ResolveAuditAssignment::class)->handle($fixture['staff']->id, $assignment->id, 3, 'redispatch', 'Authority remains withdrawn.', (string) Str::uuid());
    expect($result['code'])->toBe('ASSIGNMENT_REDISPATCH_PENDING')->and($assignment->refresh()->party_id)->toBeNull()->and($assignment->state['attempt'])->toBe(1);
});

it('journals missing reasons and stale revisions and cannot close an offered or accepted case', function (): void {
    $fixture = Fixture::make(0);
    $assignment = Fixture::request($fixture);
    Sanctum::actingAs($fixture['staff'], ['staff:audit:read', 'staff:audit:manage']);
    $body = ['expected_revision' => 1, 'request_id' => (string) Str::uuid()];
    $this->postJson('/api/v1/staff/audit-assignments/'.$assignment->id.'/close', $body)->assertUnprocessable()->assertJsonPath('code', 'ASSIGNMENT_REASON_REQUIRED');
    $this->getJson('/api/v1/staff/audit-assignments/operations/'.$body['request_id'].'?command=audit.assignment.close')->assertUnprocessable();
    $this->postJson('/api/v1/staff/audit-assignments/'.$assignment->id.'/close', [...$body, 'reason' => 'Changed.'])->assertConflict();
    $this->postJson('/api/v1/staff/audit-assignments/'.$assignment->id.'/close', ['expected_revision' => 0, 'reason' => 'Stale.', 'request_id' => (string) Str::uuid()])->assertConflict()->assertJsonPath('code', 'VERSION_CONFLICT');
    $offered = Fixture::make(1);
    $active = Fixture::request($offered);
    expect(app(ResolveAuditAssignment::class)->handle($offered['staff']->id, $active->id, 1, 'close', 'Invalid closure.', (string) Str::uuid())['code'])->toBe('ASSIGNMENT_NOT_IN_OPERATIONS');
    expect(fn () => app(AuditEngagementState::class)->close($active->state, now()->toDateTimeImmutable()))->toThrow(CommandRejection::class, 'ASSIGNMENT_NOT_IN_OPERATIONS');
    expect(fn () => app(ResolveAuditAssignment::class)->handle($offered['staff']->id, $active->id, 1, 'force', 'Invalid action.', (string) Str::uuid()))->toThrow(CommandRejection::class, 'ASSIGNMENT_RESOLUTION_INVALID');
});

it('requires a current assignment-management permission independently of API abilities and denies unknown operations', function (): void {
    $fixture = Fixture::make(0);
    $assignment = Fixture::request($fixture);
    $body = ['expected_revision' => 1, 'reason' => 'Close.', 'request_id' => (string) Str::uuid()];
    $this->getJson('/api/v1/staff/audit-assignments/'.$assignment->id)->assertUnauthorized();
    Sanctum::actingAs($fixture['staff'], []);
    $this->getJson('/api/v1/staff/audit-assignments/'.$assignment->id)->assertForbidden();
    $this->postJson('/api/v1/staff/audit-assignments/'.$assignment->id.'/close', $body)->assertForbidden();
    $this->getJson('/api/v1/staff/audit-assignments/operations/'.$body['request_id'].'?command=audit.assignment.close')->assertForbidden();
    $outsider = User::factory()->withTwoFactor()->create();
    Sanctum::actingAs($outsider, ['staff:audit:read', 'staff:audit:manage']);
    $this->getJson('/api/v1/staff/audit-assignments/'.$assignment->id)->assertForbidden();
    $this->postJson('/api/v1/staff/audit-assignments/'.$assignment->id.'/close', $body)->assertForbidden();
    Sanctum::actingAs($fixture['staff'], ['staff:audit:read', 'staff:audit:manage']);
    $this->postJson('/api/v1/staff/audit-assignments/'.$assignment->id.'/close', ['request_id' => 'bad'])->assertUnprocessable();
    $this->getJson('/api/v1/staff/audit-assignments/operations/'.$body['request_id'].'?command=unknown')->assertNotFound();
    $this->getJson('/api/v1/staff/audit-assignments/operations/'.$body['request_id'].'?command=audit.assignment.close')->assertNotFound();
    $this->getJson('/api/v1/staff/audit-assignments/operations/'.$body['request_id'])->assertUnprocessable();
    $receipt = $this->postJson('/api/v1/staff/audit-assignments/'.$assignment->id.'/close', $body)->assertOk()->json();
    app(ConfigureStaffAccess::class)->handle($fixture['staff']->id, true, 'Remove assignment management.', (string) Str::uuid(), ['analyst']);
    $this->getJson($receipt['data']['next']['url'])->assertForbidden();
    $this->getJson('/api/v1/staff/audit-assignments/operations/'.$body['request_id'].'?command=audit.assignment.close')->assertForbidden();
});

it('does not treat a staff journal entry for another resource as an assignment resolution', function (): void {
    $fixture = Fixture::make(0);
    $id = (string) Str::uuid();
    app(OperationJournal::class)->execute('staff:'.$fixture['staff']->id, $fixture['staff']->id, 'audit.assignment.close', $id, 'wrong', 'wrong', [],
        function (): void {}, fn (): OperationResult => new OperationResult('SYNTHETIC', [], 1));
    expect(fn () => app(FindAuditResolutionOperation::class)->handle($fixture['staff']->id, 'audit.assignment.close', $id))->toThrow(CommandRejection::class, 'OPERATION_NOT_FOUND');
});

it('enforces that closed cases have no recipient and cannot masquerade as completed reports', function (): void {
    $fixture = Fixture::make(1);
    $assignment = Fixture::request($fixture);
    expect(fn () => DB::transaction(fn (): bool => $assignment->forceFill(['status' => 'closed'])->save()))->toThrow(QueryException::class);
    expect(fn () => DB::transaction(fn (): bool => $assignment->forceFill(['status' => 'closed', 'party_id' => null, 'completed_at' => now()])->save()))->toThrow(QueryException::class);
});

it('makes a closed engagement terminal in PostgreSQL even for direct record mutations', function (string $change): void {
    $fixture = Fixture::make(0);
    $assignment = Fixture::request($fixture);
    $request = (string) Str::uuid();
    $receipt = app(ResolveAuditAssignment::class)->handle($fixture['staff']->id, $assignment->id, 1, 'close', 'No eligible partner.', $request);
    $before = $assignment->refresh()->getRawOriginal();
    expect($assignment->status)->toBe('closed');
    expect(fn () => DB::transaction(function () use ($assignment, $change): void {
        $query = DB::table('audit_assignments')->where('id', $assignment->id);
        if ($change === 'delete') {
            $query->delete();

            return;
        }
        $query->update(match ($change) {
            'reopen' => ['status' => 'operations'],
            'revision' => ['revision' => $assignment->revision + 1],
            'deadline' => ['accept_by' => now()->addHour()],
            'state' => ['state' => 'replacement-state'],
            default => throw new LogicException('Unknown closed engagement mutation.'),
        });
    }))->toThrow(QueryException::class, 'Closed audit assignments are immutable');
    expect($assignment->refresh()->getRawOriginal())->toBe($before)
        ->and(app(ResolveAuditAssignment::class)->handle($fixture['staff']->id, $assignment->id, 1, 'close', 'No eligible partner.', $request))->toBe($receipt);
})->with(['reopen', 'revision', 'deadline', 'state', 'delete']);

it('refuses a protection rollback with closed records and preserves their database guard', function (): void {
    $fixture = Fixture::make(0);
    $assignment = Fixture::request($fixture);
    app(ResolveAuditAssignment::class)->handle($fixture['staff']->id, $assignment->id, 1, 'close', 'No eligible partner.', (string) Str::uuid());
    $before = $assignment->refresh()->getRawOriginal();
    $migration = require database_path('migrations/2026_09_25_041046_protect_closed_audit_assignments.php');
    expect(fn () => $migration->down())->toThrow(QueryException::class, 'Closed audit assignments require a forward migration; rollback is refused');
    expect(fn () => DB::transaction(fn (): int => DB::table('audit_assignments')->where('id', $assignment->id)->update(['status' => 'operations'])))
        ->toThrow(QueryException::class, 'Closed audit assignments are immutable');
    expect($assignment->refresh()->getRawOriginal())->toBe($before);
});

it('reverses and reapplies unused closed-engagement protection without changing assignment data', function (): void {
    $fixture = Fixture::make(0);
    $assignment = Fixture::request($fixture);
    $before = $assignment->getRawOriginal();
    $migration = require database_path('migrations/2026_09_25_041046_protect_closed_audit_assignments.php');
    $guard = fn (): bool => DB::table('pg_trigger')->where('tgname', 'audit_assignment_closed_immutable')
        ->whereRaw('tgrelid = ?::regclass', ['audit_assignments'])->exists();
    expect($guard())->toBeTrue();
    $migration->down();
    expect($guard())->toBeFalse()->and($assignment->refresh()->getRawOriginal())->toBe($before);
    $migration->up();
    expect($guard())->toBeTrue()->and($assignment->refresh()->getRawOriginal())->toBe($before);
    app(ResolveAuditAssignment::class)->handle($fixture['staff']->id, $assignment->id, 1, 'close', 'No eligible partner.', (string) Str::uuid());
    expect(fn () => DB::transaction(fn (): int => DB::table('audit_assignments')->where('id', $assignment->id)->delete()))
        ->toThrow(QueryException::class, 'Closed audit assignments are immutable');
});
