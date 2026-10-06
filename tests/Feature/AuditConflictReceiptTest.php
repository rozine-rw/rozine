<?php

declare(strict_types=1);

use App\Application\Auditor\GetAuditAssignment;
use App\Application\Auditor\GetOwnAuditConflict;
use App\Application\Auditor\ListOwnAuditConflicts;
use App\Application\Identity\SelectActiveRole;
use App\Domain\Identity\IdentityViolation;
use App\Domain\Operations\CommandRejection;
use App\Models\AuditConflictDeclaration;
use App\Models\CommandOperation;
use App\Models\RoleMembership;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Support\AuditAssignmentFixture as Fixture;
use Tests\Support\AuditorFixture;
use Tests\Support\BusinessAuthorityFixture;

beforeEach(function (): void {
    $this->freezeTime();
});

it('returns only the declaring Partys receipt across logins after blocking reassignment', function (): void {
    $fixture = Fixture::make();
    $assignment = Fixture::request($fixture);
    $partner = Fixture::recipient($fixture, $assignment);
    $operation = Fixture::respond($partner['user'], $assignment, 'conflict', 'Private family relationship.', 'family_or_business');
    $assignment->refresh();
    $replacement = Fixture::recipient($fixture, $assignment);
    $declaration = AuditConflictDeclaration::query()->firstOrFail();
    $receipt = app(GetOwnAuditConflict::class)->handle($partner['user']->id, 1, $assignment->id);
    expect($receipt)->toBe(['assignment_id' => $assignment->id, 'business_id' => $fixture['business'],
        'conflict' => ['conflict_id' => $declaration->id, 'kind' => 'family_or_business', 'declared_at' => $declaration->created_at->toIso8601String(),
            'note' => 'Private family relationship.', 'blocking' => true, 'status' => 'reassigned']])
        ->and($operation['data']['conflict_id'])->toBe($declaration->id)
        ->and($operation['data']['outcome'])->toBe(['resolution' => 'reassigned'])
        ->and(json_encode($receipt, JSON_THROW_ON_ERROR))->not->toContain($replacement['party']->id, 'selection_basis', 'licence', 'certificate')
        ->and(json_encode(CommandOperation::query()->where('command', 'conflict.declare')->firstOrFail()->result, JSON_THROW_ON_ERROR))->not->toContain('Private family relationship.');
    $second = User::factory()->withTwoFactor()->for($partner['party'])->create();
    app(SelectActiveRole::class)->handle($second->id, 'auditor', 0, (string) Str::uuid());
    expect(app(GetOwnAuditConflict::class)->handle($second->id, 1, $assignment->id))->toBe($receipt)
        ->and(app(ListOwnAuditConflicts::class)->handle($second->id, 1))->toBe(['data' => [$receipt], 'next_cursor' => null]);
    foreach ([$assignment->id, (string) Str::ulid()] as $id) {
        expect(fn () => app(GetOwnAuditConflict::class)->handle($replacement['user']->id, 1, $id))->toThrow(CommandRejection::class, 'AUDIT_CONFLICT_NOT_FOUND');
    }
    expect(app(ListOwnAuditConflicts::class)->handle($replacement['user']->id, 1))->toBe(['data' => [], 'next_cursor' => null]);
    expect(fn () => app(GetAuditAssignment::class)->handle($partner['user']->id, 1, $assignment->id))->toThrow(CommandRejection::class, 'ASSIGNMENT_NOT_FOUND');
});

it('keeps the own declaration readable when standing is suspended and Business authority has lapsed', function (): void {
    $fixture = Fixture::make(1);
    $assignment = Fixture::request($fixture);
    $partner = $fixture['partners'][0];
    $operation = Fixture::respond($partner['user'], $assignment, 'conflict', 'New financial interest.', 'financial_interest');
    $receipt = app(GetOwnAuditConflict::class)->handle($partner['user']->id, 1, $assignment->id);
    AuditorFixture::review($partner['staff'], $partner['party']->id, 3, 'suspend');
    $authority = $fixture['authority'];
    $authority['terms']['status'] = 'revoked';
    BusinessAuthorityFixture::configure($authority, 1);
    expect($operation['data']['outcome'])->toBe(['resolution' => 'reassignment_pending'])
        ->and($receipt['conflict']['status'])->toBe('reassignment_pending')
        ->and(app(GetOwnAuditConflict::class)->handle($partner['user']->id, 1, $assignment->id))->toBe($receipt)
        ->and(app(ListOwnAuditConflicts::class)->handle($partner['user']->id, 1))->toBe(['data' => [$receipt], 'next_cursor' => null]);
});

it('paginates only the actors conflict history without duplicates or private unbounded reads', function (): void {
    $partner = AuditorFixture::make();
    $records = AuditConflictDeclaration::factory()->count(3)->create(['party_id' => $partner['party']->id]);
    AuditConflictDeclaration::factory()->create();
    $ordered = $records->sortByDesc('id')->values();
    $list = app(ListOwnAuditConflicts::class);
    $first = $list->handle($partner['user']->id, 1, limit: 2);
    expect(array_column(array_column($first['data'], 'conflict'), 'conflict_id'))->toBe([$ordered[0]->id, $ordered[1]->id])
        ->and($first['next_cursor'])->toBe($ordered[1]->id);
    $second = $list->handle($partner['user']->id, 1, $first['next_cursor'], 2);
    expect(array_column(array_column($second['data'], 'conflict'), 'conflict_id'))->toBe([$ordered[2]->id])->and($second['next_cursor'])->toBeNull();
    expect($list->handle($partner['user']->id, 1, $ordered[2]->id, 2))->toBe(['data' => [], 'next_cursor' => null]);
    DB::flushQueryLog();
    DB::enableQueryLog();
    $list->handle($partner['user']->id, 1, limit: 50);
    $queries = DB::getQueryLog();
    DB::disableQueryLog();
    expect(count(array_filter($queries, fn (array $query): bool => str_contains($query['query'], '"audit_conflict_declarations"'))))->toBe(1);
});

it('rejects invalid conflict pagination bounds', function (?string $before, int $limit): void {
    $partner = AuditorFixture::make();
    expect(fn () => app(ListOwnAuditConflicts::class)->handle($partner['user']->id, 1, $before, $limit))
        ->toThrow(CommandRejection::class, 'AUDIT_CONFLICT_PAGE_INVALID');
})->with([[null, 0], [null, 51], [null, 100], [null, 101], ['', 25], ['unknown', 25]]);

it('uses the same bounded conflict page through the port and HTTP transport', function (): void {
    $partner = AuditorFixture::make();
    AuditConflictDeclaration::factory()->count(51)->create(['party_id' => $partner['party']->id]);
    $page = app(ListOwnAuditConflicts::class)->handle($partner['user']->id, 1, limit: 50);
    expect($page['data'])->toHaveCount(50)->and($page['next_cursor'])->not->toBeNull();
    Sanctum::actingAs($partner['user'], ['auditor:read']);
    $wire = $this->getJson('/api/v1/auditor/conflicts?limit=50')->assertOk()->assertJsonCount(50, 'data.conflicts')->json('data');
    $last = $this->getJson($wire['pagination']['next']['url'])->assertOk()->assertJsonCount(1, 'data.conflicts')
        ->assertJsonPath('data.pagination.next', null)->json('data.conflicts');
    $all = [...array_column($wire['conflicts'], 'assignment_id'), ...array_column($last, 'assignment_id')];
    expect(array_unique($all))->toHaveCount(51);
    foreach ([0, 51, 100] as $limit) {
        $this->getJson('/api/v1/auditor/conflicts?limit='.$limit)->assertUnprocessable()->assertJsonValidationErrors('limit');
    }
});

it('requires current Auditor identity role and MFA for receipt and register access', function (string $fault, string $code): void {
    $partner = AuditorFixture::make();
    $record = AuditConflictDeclaration::factory()->create(['party_id' => $partner['party']->id]);
    $context = 1;
    if ($fault === 'mfa') {
        $partner['user']->forceFill(['two_factor_confirmed_at' => null])->save();
    } elseif ($fault === 'membership') {
        RoleMembership::query()->where('party_id', $partner['party']->id)->update(['status' => 'suspended']);
    } elseif ($fault === 'context') {
        $context = 0;
    } elseif ($fault === 'unlinked') {
        $partner['user']->forceFill(['party_id' => null])->save();
    } else {
        $partner['user']->forceFill(['active_membership_id' => null])->save();
    }
    foreach ([
        fn () => app(GetOwnAuditConflict::class)->handle($partner['user']->id, $context, $record->assignment_id),
        fn () => app(ListOwnAuditConflicts::class)->handle($partner['user']->id, $context),
    ] as $read) {
        expect($read)->toThrow(IdentityViolation::class, $code);
    }
})->with([
    ['mfa', 'MFA_REQUIRED'], ['membership', 'ROLE_MEMBERSHIP_REQUIRED'], ['context', 'ACTIVE_ROLE_REVISION_CONFLICT'],
    ['unlinked', 'IDENTITY_NOT_LINKED'], ['inactive', 'ACTIVE_ROLE_REQUIRED'],
]);
