<?php

declare(strict_types=1);

use App\Application\Disbursement\ManageDisbursements;
use App\Application\Identity\Contracts\IdentityAccessStore;
use App\Application\Identity\RecordStaffPerson;
use App\Domain\Disbursement\StaffIndependence;
use App\Domain\Identity\IdentityViolation;
use App\Domain\Operations\CommandRejection;
use App\Infrastructure\Disbursement\EloquentStaffConnections;
use App\Models\BusinessProfile;
use App\Models\DisbursementIndependenceDeclaration;
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
use Tests\Support\BusinessAuthorityFixture;
use Tests\Support\DisbursementFixture;

/*
 * The real staff-person connection source (#96 5956110941, owner decision 5956161592): an identity
 * operator links a staff account to its verified person; a visible connection to the Business or
 * its Investors is `connected`; only the staff member's own signed declaration for the disbursement
 * makes them `unconnected`; anything unestablished stays `unavailable`.
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

/** @param  list<string>  $partyIds */
function staffConnection(User $staff, string $disbursementId, string $operationId, string $businessId, array $partyIds = []): string
{
    return DB::transaction(fn (): string => app(EloquentStaffConnections::class)->connection($staff->id, $disbursementId, $operationId, $businessId, $partyIds));
}

/** Retains a declaration under the staff member's current staff-person revision and returns its operation id. */
function declareIndependence(User $staff, string $disbursementId, ?string $sha256 = null): string
{
    $operationId = strtolower((string) Str::ulid());
    (new DisbursementIndependenceDeclaration)->forceFill(['disbursement_id' => $disbursementId, 'staff_user_id' => $staff->id,
        'staff_person_identity_id' => app(IdentityAccessStore::class)->staffPerson($staff->id)['resolution_id'] ?? null, 'command' => 'authorize',
        'operation_id' => $operationId, 'statement_version' => StaffIndependence::VERSION,
        'statement_sha256' => $sha256 ?? StaffIndependence::statementSha256(), 'declared_at' => now()])->save();

    return $operationId;
}

/** Gives a verified person Party a known identity reference, replacing the factory's random digest. */
function knownIdentity(string $partyId, string $reference): void
{
    DB::table('verified_person_identities')->where('party_id', $partyId)->delete();
    VerifiedPersonIdentity::factory()->create(['identity_digest' => hash('sha256', $reference), 'party_id' => $partyId]);
}

/** @return array{business: string, people: list<Party>, entity: string} */
function independenceBusiness(): array
{
    $fixture = BusinessAuthorityFixture::make('organization', 2, 'COMPANY-IND');
    BusinessAuthorityFixture::configure($fixture);

    return ['business' => BusinessProfile::query()->where('entity_party_id', $fixture['entity'])->sole()->id, 'people' => $fixture['people'], 'entity' => $fixture['entity']];
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

    $events = IdentityAuditEvent::query()->where('action', 'staff.person.record')->orderBy('created_at')->get();
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

it('answers unavailable, connected or unconnected from identity, visible connections and the signed declaration', function (): void {
    $business = independenceBusiness();
    $operator = independenceOperator();
    $disbursement = strtolower((string) Str::ulid());
    $investor = Party::factory()->verified()->create();
    /** @return array{0: User, 1: string} */
    $person = function (string $reference, ?Party $party) use ($operator, $disbursement): array {
        if ($party !== null) {
            knownIdentity($party->id, $reference);
        }
        $staff = DisbursementFixture::staff(['treasury']);
        linkStaffPerson($operator, $staff, $reference);

        return [$staff, declareIndependence($staff, $disbursement)];
    };
    $unresolved = DisbursementFixture::staff(['treasury']);
    $unresolvedOperation = declareIndependence($unresolved, $disbursement);
    [$independent, $independentOperation] = $person('nid:independent', null);
    $undeclared = DisbursementFixture::staff(['treasury']);
    linkStaffPerson($operator, $undeclared, 'nid:undeclared');
    $otherDisbursement = DisbursementFixture::staff(['treasury']);
    linkStaffPerson($operator, $otherDisbursement, 'nid:other-disbursement');
    $elsewhere = declareIndependence($otherDisbursement, strtolower((string) Str::ulid()));
    $oldStatement = DisbursementFixture::staff(['treasury']);
    linkStaffPerson($operator, $oldStatement, 'nid:old-statement');
    $oldOperation = declareIndependence($oldStatement, $disbursement, hash('sha256', 'staff-independence-v0'));
    [$signatory, $signatoryOperation] = $person('nid:signatory', $business['people'][1]);
    [$investing, $investingOperation] = $person('nid:investor', $investor);

    expect(staffConnection($unresolved, $disbursement, $unresolvedOperation, $business['business']))->toBe('unavailable')
        ->and(staffConnection($independent, $disbursement, $independentOperation, $business['business'], [$investor->id]))->toBe('unconnected')
        ->and(staffConnection($independent, $disbursement, strtolower((string) Str::ulid()), $business['business']))->toBe('unavailable')
        ->and(staffConnection($signatory, $disbursement, $independentOperation, $business['business']))->toBe('connected')
        ->and(staffConnection($undeclared, $disbursement, $independentOperation, $business['business']))->toBe('unavailable')
        ->and(staffConnection($otherDisbursement, $disbursement, $elsewhere, $business['business']))->toBe('unavailable')
        ->and(staffConnection($oldStatement, $disbursement, $oldOperation, $business['business']))->toBe('unavailable')
        ->and(staffConnection($signatory, $disbursement, $signatoryOperation, $business['business']))->toBe('connected')
        ->and(staffConnection($investing, $disbursement, $investingOperation, $business['business'], [$investor->id]))->toBe('connected')
        ->and(staffConnection($independent, $disbursement, $independentOperation, strtolower((string) Str::ulid())))->toBe('unavailable');
});

it('never lets a declaration signed under one staff-person link vouch for a later link', function (): void {
    $business = independenceBusiness();
    $operator = independenceOperator();
    $staff = DisbursementFixture::staff(['treasury']);
    $disbursement = strtolower((string) Str::ulid());
    linkStaffPerson($operator, $staff, 'nid:person-a');
    $signed = declareIndependence($staff, $disbursement);

    expect(staffConnection($staff, $disbursement, $signed, $business['business']))->toBe('unconnected');
    linkStaffPerson($operator, $staff, null);
    expect(staffConnection($staff, $disbursement, $signed, $business['business']))->toBe('unavailable');
    linkStaffPerson($operator, $staff, 'nid:person-b');
    expect(staffConnection($staff, $disbursement, $signed, $business['business']))->toBe('unavailable');
    linkStaffPerson($operator, $staff, 'nid:person-a');
    expect(staffConnection($staff, $disbursement, $signed, $business['business']))->toBe('unavailable')
        ->and(DisbursementIndependenceDeclaration::query()->where('operation_id', $signed)->count())->toBe(1);

    $resigned = declareIndependence($staff, $disbursement);
    expect(staffConnection($staff, $disbursement, $resigned, $business['business']))->toBe('unconnected');
});

it('treats the Business entity Party itself as connected', function (): void {
    $fixture = BusinessAuthorityFixture::make();
    BusinessAuthorityFixture::configure($fixture);
    $business = BusinessProfile::query()->where('entity_party_id', $fixture['entity'])->sole()->id;
    knownIdentity($fixture['entity'], 'nid:sole-trader');
    $staff = DisbursementFixture::staff(['approver']);
    linkStaffPerson(independenceOperator(), $staff, 'nid:sole-trader');
    $disbursement = strtolower((string) Str::ulid());

    expect(staffConnection($staff, $disbursement, declareIndependence($staff, $disbursement), $business))->toBe('connected');
});

it('records the actor’s declaration with an accepted authorize, never with a refused one, and binds it to the request', function (): void {
    ['disbursement' => $disbursement] = DisbursementFixture::funded();
    $maker = DisbursementFixture::staff(['treasury']);
    $manage = app(ManageDisbursements::class);
    $request = (string) Str::uuid();

    expect($manage->command($maker->id, $disbursement->id, 'authorize', 9, 'Checked against the funded campaign.', (string) Str::uuid(), null, true)['code'])
        ->toBe('VERSION_CONFLICT')
        ->and(DisbursementIndependenceDeclaration::query()->count())->toBe(0);

    $accepted = $manage->command($maker->id, $disbursement->id, 'authorize', 0, 'Checked against the funded campaign.', $request, null, true);
    $declaration = DisbursementIndependenceDeclaration::query()->sole();

    expect($accepted['status'])->toBe('completed')
        ->and($declaration->only(['disbursement_id', 'staff_user_id', 'command', 'statement_version', 'statement_sha256']))->toBe([
            'disbursement_id' => $disbursement->id, 'staff_user_id' => $maker->id, 'command' => 'authorize',
            'statement_version' => StaffIndependence::VERSION, 'statement_sha256' => StaffIndependence::statementSha256()])
        ->and($declaration->operation_id)->toBe($accepted['operation_id'])
        ->and($declaration->staff_person_identity_id)->toBeNull()
        ->and($manage->command($maker->id, $disbursement->id, 'authorize', 0, 'Checked against the funded campaign.', $request, null, true))->toBe($accepted)
        ->and(fn () => $manage->command($maker->id, $disbursement->id, 'authorize', 0, 'Checked against the funded campaign.', $request))
        ->toThrow(CommandRejection::class, 'IDEMPOTENCY_CONFLICT')
        ->and(DisbursementIndependenceDeclaration::query()->count())->toBe(1);
});

it('accepts the declaration only on authorize and approve, and shows the statement it records', function (): void {
    ['disbursement' => $disbursement] = DisbursementFixture::funded();
    $treasury = DisbursementFixture::staff(['treasury']);
    linkStaffPerson(independenceOperator(), $treasury, 'nid:treasury');
    $this->actingAs($treasury);
    $body = ['request_id' => (string) Str::uuid(), 'expected_revision' => 0, 'reason' => 'Checked against the funded campaign.', 'independence_declared' => true];

    $this->postJson(route('staff.disbursements.hold', ['disbursement' => $disbursement->id]), $body)
        ->assertUnprocessable()->assertJsonValidationErrors('independence_declared');
    $this->postJson(route('staff.disbursements.authorize', ['disbursement' => $disbursement->id]), [...$body, 'independence_declared' => 'maybe'])
        ->assertUnprocessable()->assertJsonValidationErrors('independence_declared');
    $this->postJson(route('staff.disbursements.authorize', ['disbursement' => $disbursement->id]), $body)->assertOk();

    expect(DisbursementIndependenceDeclaration::query()->sole()->only(['staff_user_id', 'staff_person_identity_id']))
        ->toBe(['staff_user_id' => $treasury->id, 'staff_person_identity_id' => StaffPersonIdentity::query()->sole()->id])
        ->and($this->get(route('staff.disbursements.show', ['disbursement' => $disbursement->id]))
            ->viewData('page')['props']['disbursement']['independence'])->toBe(['version' => StaffIndependence::VERSION, 'statement' => StaffIndependence::STATEMENT]);
});

it('keeps staff independence evidence append-only and refuses to roll it back once recorded', function (): void {
    $staff = DisbursementFixture::staff(['treasury']);
    linkStaffPerson(independenceOperator(), $staff, 'nid:append-only');
    declareIndependence($staff, strtolower((string) Str::ulid()));

    foreach (['UPDATE staff_person_identities SET revision = 9', 'DELETE FROM staff_person_identities',
        'UPDATE disbursement_independence_declarations SET command = \'approve\'', 'DELETE FROM disbursement_independence_declarations'] as $sql) {
        expect(fn () => DB::transaction(fn () => DB::unprepared($sql)))->toThrow(QueryException::class, 'Staff independence evidence is append-only');
    }
    expect(fn () => (require database_path('migrations/2026_10_02_170000_create_staff_independence_evidence.php'))->down())
        ->toThrow(QueryException::class, 'Recorded staff independence evidence requires a forward migration');
});
