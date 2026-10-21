<?php

declare(strict_types=1);

use App\Application\Identity\Contracts\IdentityAccessStore;
use App\Application\Identity\RecordStaffPerson;
use App\Domain\Identity\IdentityViolation;
use App\Models\IdentityAuditEvent;
use App\Models\IdentityOperator;
use App\Models\Party;
use App\Models\StaffPersonIdentity;
use App\Models\User;
use App\Models\VerifiedPersonIdentity;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Support\DisbursementFixture;

/*
 * Staff-person records (#96 5956110941): an identity operator links a staff account to its verified
 * person as append-only revisions. Since BRS v2.0 SVC-R01 nothing about a disbursement depends on
 * them: no staff member authorizes or approves a payout, so no independence declaration is asked
 * for, and the declarations recorded under the retired maker/checker release stay as retained,
 * append-only evidence.
 */

function independenceOperator(): User
{
    $user = User::factory()->withTwoFactor()->create();
    IdentityOperator::factory()->create(['user_id' => $user->id]);

    return $user;
}

/** @return array<string, mixed> */
function linkStaffPerson(User $operator, User $staff, ?string $reference, ?string $requestId = null, string $evidence = 'review:staff-1'): array
{
    return app(RecordStaffPerson::class)->handle($operator->id, $staff->id, $reference, $evidence, 'Verified the staff member’s identity document.', $requestId ?? (string) Str::uuid());
}

/** Retains a legacy declaration, as the retired authorize command recorded it, and returns its operation id. */
function retainedDeclaration(User $staff, string $disbursementId): string
{
    $operationId = strtolower((string) Str::ulid());
    DB::table('disbursement_independence_declarations')->insert(['id' => strtolower((string) Str::ulid()), 'disbursement_id' => $disbursementId,
        'staff_user_id' => $staff->id, 'staff_person_identity_id' => app(IdentityAccessStore::class)->staffPerson($staff->id)['resolution_id'] ?? null,
        'command' => 'authorize', 'operation_id' => $operationId, 'statement_version' => 'staff-independence-v1',
        'statement_sha256' => hash('sha256', 'staff-independence-v1'), 'declared_at' => now()]);

    return $operationId;
}

/** Gives a verified person Party a known identity reference, replacing the factory's random digest. */
function knownIdentity(string $partyId, string $reference): void
{
    DB::table('verified_person_identities')->where('party_id', $partyId)->delete();
    VerifiedPersonIdentity::factory()->create(['identity_digest' => hash('sha256', $reference), 'party_id' => $partyId]);
}

it('records and revokes a staff person as append-only revisions with one audit event per request', function (): void {
    $operator = independenceOperator();
    $staff = DisbursementFixture::staff(['treasury']);
    $request = (string) Str::uuid();

    $first = linkStaffPerson($operator, $staff, 'nid:1199880012345678', $request);

    expect($first)->toMatchArray(['code' => 'STAFF_PERSON_RECORDED', 'user_id' => $staff->id, 'contract_version' => 'identity-management-v1'])
        ->and(linkStaffPerson($operator, $staff, 'nid:1199880012345678', $request))->toBe($first)
        ->and(fn () => linkStaffPerson($operator, $staff, 'nid:other', $request))->toThrow(IdentityViolation::class, 'IDEMPOTENCY_KEY_REUSED')
        ->and(app(IdentityAccessStore::class)->staffPerson($staff->id))->toBe(['resolution_id' => StaffPersonIdentity::query()->sole()->id, 'party_id' => null]);

    expect(linkStaffPerson($operator, $staff, null)['code'])->toBe('STAFF_PERSON_REVOKED')
        ->and(app(IdentityAccessStore::class)->staffPerson($staff->id))->toBeNull()
        ->and(fn () => linkStaffPerson($operator, $staff, null))->toThrow(IdentityViolation::class, 'STAFF_PERSON_NOT_RESOLVED')
        ->and(StaffPersonIdentity::query()->orderBy('revision')->get(['revision', 'status'])->map(fn (StaffPersonIdentity $row): string => $row->revision.':'.$row->status)->all())
        ->toBe(['1:active', '2:revoked']);

    /* Both events can share a timestamp, so order them by the revision each one records. */
    $events = IdentityAuditEvent::query()->where('action', 'staff.person.record')->get()
        ->sortBy(fn (IdentityAuditEvent $event): int => (int) $event->after['revision'])->values();
    expect($events)->toHaveCount(2)
        ->and($events[0]->after)->toMatchArray(['revision' => 1, 'status' => 'active', 'identity_digest' => hash('sha256', 'nid:1199880012345678')])
        ->and($events[1]->before)->toEqual(['revision' => 1, 'status' => 'active']);
});

it('resolves a staff person to the verified Party that holds the same identity', function (): void {
    $party = Party::factory()->verified()->create();
    knownIdentity($party->id, 'nid:shared-person');
    $staff = DisbursementFixture::staff(['approver']);
    linkStaffPerson(independenceOperator(), $staff, 'nid:shared-person');

    expect(app(IdentityAccessStore::class)->staffPerson($staff->id))->toBe(['resolution_id' => StaffPersonIdentity::query()->sole()->id, 'party_id' => $party->id]);
});

it('refuses a staff person record from a non-operator, for a non-staff account or with invalid input', function (Closure $call, string $code): void {
    expect($call)->toThrow(IdentityViolation::class, $code);
})->with([
    'not an operator' => [fn () => linkStaffPerson(DisbursementFixture::staff(['compliance']), DisbursementFixture::staff(['treasury']), 'nid:1'), 'IDENTITY_OPERATOR_REQUIRED'],
    'not a staff account' => [fn () => linkStaffPerson(independenceOperator(), User::factory()->create(), 'nid:1'), 'STAFF_ACCOUNT_NOT_FOUND'],
    'unknown account' => [fn () => app(RecordStaffPerson::class)->handle(independenceOperator()->id, 999999, 'nid:1', 'review:1', 'Reason.', (string) Str::uuid()), 'STAFF_ACCOUNT_NOT_FOUND'],
    'malformed reference' => [fn () => linkStaffPerson(independenceOperator(), DisbursementFixture::staff(['treasury']), 'no colon'), 'IDENTITY_REFERENCE_INVALID'],
    'blank evidence' => [fn () => linkStaffPerson(independenceOperator(), DisbursementFixture::staff(['treasury']), 'nid:1', evidence: ' '), 'IDENTITY_EVIDENCE_REQUIRED'],
]);

it('records a staff person through web and API with the same result', function (): void {
    $operator = independenceOperator();
    $staff = DisbursementFixture::staff(['treasury']);
    $payload = ['user_id' => $staff->id, 'status' => 'active', 'identity_reference' => 'nid:web-api', 'evidence_reference' => 'review:2',
        'reason' => 'Checked the identity document.', 'request_id' => (string) Str::uuid()];

    $web = $this->actingAs($operator)->postJson(route('identity.staff-people.record'), $payload)->assertOk()->json();
    Sanctum::actingAs($operator, ['business:read']);
    $this->postJson(route('api.v1.identity.staff-people.record'), $payload)->assertForbidden();
    Sanctum::actingAs($operator, ['identity:manage']);
    $this->postJson(route('api.v1.identity.staff-people.record'), $payload)->assertOk()->assertExactJson($web);

    expect($web['data'])->toMatchArray(['code' => 'STAFF_PERSON_RECORDED', 'user_id' => $staff->id, 'party_id' => null]);
    $this->postJson(route('api.v1.identity.staff-people.record'), [...$payload, 'status' => 'revoked', 'request_id' => (string) Str::uuid()])
        ->assertUnprocessable()->assertJsonValidationErrors('identity_reference');
    $this->postJson(route('api.v1.identity.staff-people.record'), ['user_id' => $staff->id, 'status' => 'revoked', 'evidence_reference' => 'review:3',
        'reason' => 'Left the company.', 'request_id' => (string) Str::uuid()])->assertOk()->assertJsonPath('data.code', 'STAFF_PERSON_REVOKED');
});

it('asks no staff member for an independence declaration: authorize and approve are gone and a hold records none', function (): void {
    ['disbursement' => $disbursement] = DisbursementFixture::funded();
    $treasury = DisbursementFixture::staff(['treasury']);
    linkStaffPerson(independenceOperator(), $treasury, 'nid:treasury');
    $this->actingAs($treasury);
    $body = ['request_id' => (string) Str::uuid(), 'expected_revision' => 0, 'reason' => 'Checked against the funded campaign.', 'independence_declared' => true];

    foreach (['authorize', 'approve', 'reject', 'step-up'] as $path) {
        $this->postJson('/admin/disbursements/'.$disbursement->id.'/'.$path, $body)->assertNotFound();
    }
    $this->postJson(route('staff.disbursements.hold', ['disbursement' => $disbursement->id]), $body)->assertOk();

    expect(DB::table('disbursement_independence_declarations')->count())->toBe(0)
        ->and($this->get(route('staff.disbursements.show', ['disbursement' => $disbursement->id]))->viewData('page')['props']['disbursement'])
        ->not->toHaveKey('independence');
});

it('keeps staff independence evidence append-only and refuses to roll it back once recorded', function (): void {
    $staff = DisbursementFixture::staff(['treasury']);
    linkStaffPerson(independenceOperator(), $staff, 'nid:append-only');
    retainedDeclaration($staff, strtolower((string) Str::ulid()));

    foreach (['UPDATE staff_person_identities SET revision = 9', 'DELETE FROM staff_person_identities',
        'UPDATE disbursement_independence_declarations SET command = \'approve\'', 'DELETE FROM disbursement_independence_declarations'] as $sql) {
        expect(fn () => DB::transaction(fn () => DB::unprepared($sql)))->toThrow(QueryException::class, 'Staff independence evidence is append-only');
    }
    expect(fn () => (require database_path('migrations/2026_10_02_170000_create_staff_independence_evidence.php'))->down())
        ->toThrow(QueryException::class, 'Recorded staff independence evidence requires a forward migration');
});
