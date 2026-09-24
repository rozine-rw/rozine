<?php

declare(strict_types=1);

use App\Application\Auditor\FindAuditAssignmentOperation;
use App\Application\Auditor\ProjectAuditAssignmentOperation;
use App\Application\Auditor\ResolveAuditAssignment;
use App\Application\Business\CreateBusinessApplication;
use App\Application\Business\SaveBusinessApplication;
use App\Domain\Identity\IdentityViolation;
use App\Domain\Operations\CommandRejection;
use App\Models\AuditAssignment;
use App\Models\AuditAssignmentVersion;
use App\Models\AuditorProfile;
use App\Models\RoleMembership;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;
use Tests\Support\AuditAssignmentFixture as Fixture;
use Tests\Support\AuditorFixture;
use Tests\Support\BusinessApplicationFixture;
use Tests\Support\BusinessAuthorityFixture;

beforeEach(function (): void {
    $this->freezeTime();
    $this->withoutVite();
});

/** @return array{identity_context_revision: int, expected_revision: int, request_id: string} */
function jobsHttpEnvelope(AuditAssignment $assignment): array
{
    return ['identity_context_revision' => 1, 'expected_revision' => $assignment->revision, 'request_id' => (string) Str::uuid()];
}

it('presents the same real offered case over web and API without exposing original evidence or private prose', function (): void {
    $fixture = Fixture::make(1);
    $user = $fixture['partners'][0]['user'];
    $owner = $fixture['authority']['users'][0];
    $created = app(CreateBusinessApplication::class)->handle($owner->id, 1, $fixture['business'], 0, (string) Str::uuid());
    app(SaveBusinessApplication::class)->handle($owner->id, 1, $fixture['business'], $created['data']['application']['id'], 1,
        BusinessApplicationFixture::fields(), 'raise', (string) Str::uuid());
    Fixture::statements($fixture);
    $assignment = Fixture::request($fixture);
    $web = [];
    $this->actingAs($user)->get('/auditor/jobs')->assertOk()->assertHeader('Cache-Control', 'no-store, private')
        ->assertInertia(function (Assert $page) use (&$web, $assignment): Assert {
            $web = $page->toArray()['props'];

            return $page->component('auditor/jobs')->where('eligible.0.id', $assignment->id)->where('monthly', null);
        });
    $this->get('/auditor/jobs/'.$assignment->id)->assertOk()->assertInertia(fn (Assert $page): Assert => $page
        ->component('auditor/file')->where('job.state', 'offered')->where('job.kind', 'flash')->where('blocked', null)
        ->where('file.raise.requested.amount', '8000000')->where('file.raise.term_months', 6)->where('file.raise.return_pct', null)
        ->where('file.documents', [])->where('file.prescreen', [])->where('file.reason', null)->where('links.procedure', null));
    Sanctum::actingAs($user, ['auditor:read']);
    $api = $this->getJson('/api/v1/auditor/jobs')->assertOk()->assertHeader('Cache-Control', 'no-store, private')
        ->assertJsonPath('data.eligible.0.requested', ['currency' => 'RWF', 'amount' => '8000000'])
        ->assertJsonPath('data.eligible.0.distance_km', '0.0')->assertJsonPath('data.eligible.0.sector', 'retail')
        ->assertJsonPath('data.eligible.0.dscr', null)->assertJsonPath('data.eligible.0.map', null)->assertJsonPath('data.pagination.next', null)
        ->assertJsonPath('data.eligible.0.allowed_actions', ['conflict.declare', 'assignment.decline', 'assignment.accept'])->json('data');
    expect($api['eligible'][0]['complete_by'])->toBe($web['eligible'][0]['complete_by'])
        ->and($api['eligible'][0]['accept_by'])->toBe($assignment->state['accept_by'])
        ->and($api['eligible'][0]['actions']['accept']['url'])->toBe('/api/v1/auditor/jobs/'.$assignment->id.'/accept')
        ->and($web['eligible'][0]['actions']['accept']['url'])->toBe('/auditor/jobs/'.$assignment->id.'/accept');
    $file = $this->getJson('/api/v1/auditor/jobs/'.$assignment->id)->assertOk()->json('data');
    expect(json_encode($file, JSON_THROW_ON_ERROR))->not->toContain('private-account-reference', 'Test-only business plan', 'selection_basis', 'mandate_sha256', 'source_hashes');
});

it('keeps unavailable draft fields null and maps Routine to monthly with its original visit deadline', function (): void {
    $fixture = Fixture::make(1);
    $user = $fixture['partners'][0]['user'];
    $assignment = Fixture::request($fixture, 'routine');
    Sanctum::actingAs($user, ['auditor:read', 'auditor:command']);
    $this->getJson('/api/v1/auditor/jobs')->assertOk()->assertJsonPath('data.eligible.0.kind', 'monthly')
        ->assertJsonPath('data.eligible.0.requested', null)->assertJsonPath('data.eligible.0.term_months', null)->assertJsonPath('data.eligible.0.complete_by', null);
    $this->postJson('/api/v1/auditor/jobs/'.$assignment->id.'/accept', jobsHttpEnvelope($assignment))->assertOk();
    $deadline = $assignment->refresh()->state['visit_by'];
    $this->getJson('/api/v1/auditor/jobs')->assertOk()->assertJsonPath('data.assigned.0.deadline.due_at', $deadline)
        ->assertJsonPath('data.assigned.0.status', 'in_progress')->assertJsonPath('data.assigned.0.step', null)->assertJsonPath('data.assigned.0.steps', null)
        ->assertJsonPath('data.assigned.0.reassigned_from', null)->assertJsonPath('data.assigned.0.allowed_actions', ['conflict.declare']);
    $this->travel(3)->days();
    $this->getJson('/api/v1/auditor/jobs')->assertOk()->assertJsonPath('data.assigned.0.status', 'overdue')->assertJsonPath('data.assigned.0.deadline.due_at', $deadline);
    $this->getJson('/api/v1/auditor/jobs/'.$assignment->id)->assertOk()->assertJsonPath('data.job.state', 'assigned')
        ->assertJsonPath('data.job.accept_by', null)->assertJsonPath('data.job.deadline.due_at', $deadline);
});

it('replays one acceptance across web and API with fresh response time and current actions', function (): void {
    $fixture = Fixture::make();
    $assignment = Fixture::request($fixture);
    $user = Fixture::recipient($fixture, $assignment)['user'];
    $body = jobsHttpEnvelope($assignment);
    $first = $this->actingAs($user)->postJson('/auditor/jobs/'.$assignment->id.'/accept', $body)->assertOk()
        ->assertJsonPath('code', 'ASSIGNMENT_ACCEPTED')->assertJsonPath('allowed_actions', ['conflict.declare'])
        ->assertJsonPath('data.next.url', '/auditor/jobs/'.$assignment->id)->json();
    $this->travel(1)->minutes();
    Sanctum::actingAs($user, ['auditor:read', 'auditor:command']);
    $retry = $this->postJson('/api/v1/auditor/jobs/'.$assignment->id.'/accept', $body)->assertOk()->json();
    expect($retry['operation_id'])->toBe($first['operation_id'])->and($retry['recorded_at'])->toBe($first['recorded_at'])
        ->and($retry['server_time'])->not->toBe($first['server_time']);
    $this->getJson('/api/v1/auditor/assignment-operations/'.$body['request_id'].'?command=assignment.accept')->assertOk()
        ->assertJsonPath('operation_id', $first['operation_id']);
    expect(AuditAssignmentVersion::query()->where('assignment_id', $assignment->id)->count())->toBe(2);
    Fixture::respond($user, $assignment->refresh(), 'conflict', 'Private tie.', 'other');
    $this->getJson('/api/v1/auditor/assignment-operations/'.$body['request_id'].'?command=assignment.accept')->assertOk()
        ->assertJsonPath('allowed_actions', [])->assertJsonPath('data.next.url', '/auditor/jobs');
    $this->getJson('/api/v1/auditor/jobs/'.$assignment->id)->assertNotFound();
});

it('records domain refusals for same UUID recovery and rejects a changed retry body', function (): void {
    $fixture = Fixture::make(1);
    $assignment = Fixture::request($fixture);
    $user = $fixture['partners'][0]['user'];
    $body = [...jobsHttpEnvelope($assignment), 'reason_code' => 'other', 'reason' => ''];
    Sanctum::actingAs($user, ['auditor:read', 'auditor:command']);
    $refused = $this->postJson('/api/v1/auditor/jobs/'.$assignment->id.'/decline', $body)->assertUnprocessable()
        ->assertJsonPath('code', 'ASSIGNMENT_REASON_REQUIRED')->assertJsonPath('errors.reason', ['A concise reason is required.'])->json();
    $this->getJson('/api/v1/auditor/assignment-operations/'.$body['request_id'].'?command=assignment.decline')->assertUnprocessable()
        ->assertJsonPath('operation_id', $refused['operation_id'])->assertJsonPath('field_errors.reason', $refused['errors']['reason']);
    $this->postJson('/api/v1/auditor/jobs/'.$assignment->id.'/decline', [...$body, 'reason' => 'Changed body.'])->assertConflict();
    $this->postJson('/api/v1/auditor/jobs/'.$assignment->id.'/decline', [...jobsHttpEnvelope($assignment), 'reason_code' => 'capacity'])->assertOk()
        ->assertJsonPath('code', 'ASSIGNMENT_DECLINED')->assertJsonPath('allowed_actions', [])->assertJsonPath('data.next.url', '/auditor/jobs');
    $this->getJson('/api/v1/auditor/assignment-operations/'.Str::uuid().'?command=assignment.accept')->assertNotFound();
    $this->getJson('/api/v1/auditor/assignment-operations/'.Str::uuid().'?command=unknown')->assertNotFound();
});

it('returns only the declarants private conflict receipt after access to the file is withdrawn', function (): void {
    $fixture = Fixture::make();
    $assignment = Fixture::request($fixture);
    $partner = Fixture::recipient($fixture, $assignment);
    $body = [...jobsHttpEnvelope($assignment), 'kind' => 'family_or_business', 'reason' => 'My private relationship.'];
    Sanctum::actingAs($partner['user'], ['auditor:read', 'auditor:command']);
    $receipt = $this->postJson('/api/v1/auditor/jobs/'.$assignment->id.'/conflict', $body)->assertOk()
        ->assertJsonPath('code', 'CONFLICT_RECORDED')->assertJsonPath('data.conflict.note', $body['reason'])
        ->assertJsonPath('data.conflict.blocking', true)->assertJsonPath('data.conflict.status', 'reassigned')
        ->assertJsonPath('allowed_actions', [])->assertJsonPath('data.next.url', '/auditor/jobs/'.$assignment->id.'/conflict')->json();
    $this->getJson('/api/v1/auditor/jobs/'.$assignment->id)->assertNotFound();
    $this->getJson('/api/v1/auditor/jobs/'.$assignment->id.'/conflict')->assertOk()->assertJsonCount(1, 'data.conflicts')
        ->assertJsonPath('data.conflicts.0.conflict', $receipt['data']['conflict'])->assertJsonMissingPath('data.conflicts.0.business');
    $this->getJson('/api/v1/auditor/conflicts')->assertOk()->assertJsonCount(1, 'data.conflicts')->assertJsonPath('data.pagination.next', null);
    $this->getJson('/api/v1/auditor/assignment-operations/'.$body['request_id'].'?command=conflict.declare')->assertOk()
        ->assertJsonPath('data.conflict', $receipt['data']['conflict']);
    $this->actingAs($partner['user'])->get('/auditor/conflicts')->assertOk()->assertInertia(fn (Assert $page): Assert => $page
        ->component('auditor/conflicts')->where('conflicts.0.conflict.note', $body['reason']));
    $this->get('/auditor/jobs/'.$assignment->id.'/conflict')->assertOk()->assertInertia(fn (Assert $page): Assert => $page
        ->component('auditor/conflicts')->where('conflicts.0.assignment_id', $assignment->id));
    $next = Fixture::recipient($fixture, $assignment->refresh());
    Sanctum::actingAs($next['user'], ['auditor:read']);
    $this->getJson('/api/v1/auditor/jobs/'.$assignment->id.'/conflict')->assertNotFound();
    $this->getJson('/api/v1/auditor/conflicts')->assertOk()->assertJsonCount(0, 'data.conflicts');
});

it('allows a private conflict receipt after Business authority lapses but denies it after Auditor membership withdrawal', function (): void {
    $fixture = Fixture::make(1);
    $assignment = Fixture::request($fixture);
    $partner = $fixture['partners'][0];
    $body = [...jobsHttpEnvelope($assignment), 'kind' => 'other', 'reason' => 'Private interest.'];
    Sanctum::actingAs($partner['user'], ['auditor:read', 'auditor:command']);
    $this->postJson('/api/v1/auditor/jobs/'.$assignment->id.'/conflict', $body)->assertOk()->assertJsonPath('data.outcome.resolution', 'reassignment_pending');
    app(ResolveAuditAssignment::class)->handle($fixture['staff']->id, $assignment->id, 2, 'close', 'No eligible independent partner.', (string) Str::uuid());
    $authority = $fixture['authority'];
    $authority['terms']['status'] = 'revoked';
    BusinessAuthorityFixture::configure($authority, 1);
    $this->getJson('/api/v1/auditor/conflicts')->assertOk()->assertJsonCount(1, 'data.conflicts')->assertJsonPath('data.conflicts.0.conflict.status', 'closed');
    $this->getJson('/api/v1/auditor/assignment-operations/'.$body['request_id'].'?command=conflict.declare')->assertOk()->assertJsonPath('allowed_actions', [])->assertJsonPath('data.conflict.status', 'reassignment_pending');
    RoleMembership::query()->where('party_id', $partner['party']->id)->update(['status' => 'revoked']);
    $this->getJson('/api/v1/auditor/conflicts')->assertForbidden();
    $this->getJson('/api/v1/auditor/assignment-operations/'.$body['request_id'].'?command=conflict.declare')->assertForbidden();
});

it('paginates the bounded Jobs projection without duplicating records and validates cursor envelopes', function (): void {
    $fixture = Fixture::make(1);
    $partner = $fixture['partners'][0];
    $ids = [Fixture::request($fixture)->id];
    $other = Fixture::make(0);
    Fixture::independence($other['staff'], $other['business'], $partner['party']->id);
    $ids[] = Fixture::request($other)->id;
    rsort($ids);
    Sanctum::actingAs($partner['user'], ['auditor:read']);
    $first = $this->getJson('/api/v1/auditor/jobs?limit=1')->assertOk()->assertJsonPath('data.eligible.0.id', $ids[0])->json('data');
    $this->getJson($first['pagination']['next']['url'])->assertOk()->assertJsonPath('data.eligible.0.id', $ids[1])->assertJsonPath('data.pagination.next', null);
    $this->getJson('/api/v1/auditor/jobs?before=unknown')->assertUnprocessable();
    $this->getJson('/api/v1/auditor/jobs?limit=51')->assertUnprocessable();
});

it('enforces authentication current role MFA context and per-token abilities on Jobs reads and commands', function (): void {
    $this->get('/auditor/jobs')->assertRedirect(route('login'));
    $this->getJson('/api/v1/auditor/jobs')->assertUnauthorized();
    $fixture = Fixture::make(1);
    $assignment = Fixture::request($fixture);
    $partner = $fixture['partners'][0];
    Sanctum::actingAs($partner['user'], []);
    $this->getJson('/api/v1/auditor/jobs')->assertForbidden();
    $this->getJson('/api/v1/auditor/jobs/'.$assignment->id)->assertForbidden();
    $this->getJson('/api/v1/auditor/jobs/'.$assignment->id.'/conflict')->assertForbidden();
    $this->getJson('/api/v1/auditor/conflicts')->assertForbidden();
    $this->getJson('/api/v1/auditor/assignment-operations/'.Str::uuid().'?command=assignment.accept')->assertForbidden();
    $this->postJson('/api/v1/auditor/jobs/'.$assignment->id.'/accept', jobsHttpEnvelope($assignment))->assertForbidden();
    Sanctum::actingAs($partner['user'], ['auditor:command']);
    $this->postJson('/api/v1/auditor/jobs/'.$assignment->id.'/accept', [...jobsHttpEnvelope($assignment), 'identity_context_revision' => 0])->assertConflict();
    $this->postJson('/api/v1/auditor/jobs/'.$assignment->id.'/accept', ['request_id' => 'invalid'])->assertUnprocessable();
    Sanctum::actingAs($partner['user'], ['auditor:read']);
    $partner['user']->forceFill(['two_factor_confirmed_at' => null])->save();
    $this->getJson('/api/v1/auditor/jobs')->assertForbidden()->assertJsonPath('code', 'MFA_REQUIRED');
    $unrelated = AuditorFixture::make();
    Sanctum::actingAs($unrelated['user'], ['auditor:read']);
    $this->getJson('/api/v1/auditor/jobs/'.$assignment->id)->assertNotFound();
    $this->getJson('/api/v1/auditor/jobs')->assertOk()->assertJsonCount(0, 'data.eligible');
});

it('does not hide an unexpected projection failure behind a successful command receipt', function (): void {
    $fixture = Fixture::make(1);
    $assignment = Fixture::request($fixture);
    $partner = $fixture['partners'][0];
    $receipt = Fixture::respond($partner['user'], $assignment);
    $event = 'eloquent.retrieved: '.AuditorProfile::class;
    Event::listen($event, function (): never {
        throw new CommandRejection('SYNTHETIC_PROJECTION_UNAVAILABLE', 503);
    });
    try {
        expect(fn () => app(ProjectAuditAssignmentOperation::class)->handle($partner['user']->id, 1, $receipt))
            ->toThrow(CommandRejection::class, 'SYNTHETIC_PROJECTION_UNAVAILABLE');
    } finally {
        Event::forget($event);
    }
});

it('keeps a command outcome immutable while clearing actions after Business verification or actor authority is lost', function (): void {
    $fixture = Fixture::make(1);
    $assignment = Fixture::request($fixture);
    $partner = $fixture['partners'][0];
    Sanctum::actingAs($partner['user'], ['auditor:read', 'auditor:command']);
    $body = jobsHttpEnvelope($assignment);
    $first = $this->postJson('/api/v1/auditor/jobs/'.$assignment->id.'/accept', $body)->assertOk()->json();
    $fixture['authority']['people'][0]->forceFill(['verified_at' => null])->save();
    $this->getJson('/api/v1/auditor/assignment-operations/'.$body['request_id'].'?command=assignment.accept')->assertOk()
        ->assertJsonPath('operation_id', $first['operation_id'])->assertJsonPath('allowed_actions', [])->assertJsonPath('data.next.url', '/auditor/jobs');
    $receipt = app(FindAuditAssignmentOperation::class)->handle($partner['user']->id, 1, 'assignment.accept', $body['request_id']);
    $partner['user']->forceFill(['two_factor_confirmed_at' => null])->save();
    expect(fn () => app(ProjectAuditAssignmentOperation::class)->handle($partner['user']->id, 1, $receipt))
        ->toThrow(IdentityViolation::class, 'MFA_REQUIRED');
});
