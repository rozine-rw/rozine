<?php

declare(strict_types=1);

use App\Application\Auditor\AdvanceAuditAssignment;
use App\Application\Auditor\AdvanceExpiredAuditOffers;
use App\Application\Auditor\FindAuditAssignmentOperation;
use App\Application\Auditor\GetAuditAssignment;
use App\Application\Auditor\MarkAuditLocationMoved;
use App\Application\Auditor\RecordAuditorIndependence;
use App\Application\Auditor\RequestAuditAssignment;
use App\Application\Identity\Contracts\IdentityAccessStore;
use App\Application\Operations\Contracts\OperationJournal;
use App\Domain\Auditor\AuditEngagementState;
use App\Domain\Identity\IdentityViolation;
use App\Domain\Operations\CommandRejection;
use App\Domain\Operations\OperationResult;
use App\Models\AuditAssignment;
use App\Models\AuditAssignmentVersion;
use App\Models\AuditConflictDeclaration;
use App\Models\AuditorIndependenceReview;
use App\Models\AuditorProfile;
use App\Models\CommandOperation;
use App\Models\RoleMembership;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\Support\AuditAssignmentFixture as Fixture;
use Tests\Support\AuditorFixture;
use Tests\Support\AuditorIndependenceFixture;
use Tests\Support\BusinessAuthorityFixture;

beforeEach(function (): void {
    $this->freezeTime();
});

it('persists a deterministic offer and its original clock once and resumes an open engagement', function (): void {
    $fixture = Fixture::make();
    $request = (string) Str::uuid();
    $record = Fixture::request($fixture, requestId: $request);
    $ids = array_column(array_map(fn (array $partner): array => ['id' => $partner['party']->id], $fixture['partners']), 'id');
    sort($ids);
    expect($record->party_id)->toBe($ids[0])->and($record->state['attempt'])->toBe(1)
        ->and($record->state['accept_by'])->toBe(now('UTC')->addHour()->format('Y-m-d\TH:i:s\Z'))
        ->and($record->state['complete_by'])->toBe(now('UTC')->addDay()->format('Y-m-d\TH:i:s\Z'))
        ->and(Fixture::request($fixture, requestId: $request)->id)->toBe($record->id)
        ->and(Fixture::request($fixture)->id)->toBe($record->id);
    $this->assertDatabaseCount('audit_assignments', 1);
    $this->assertDatabaseCount('audit_assignment_versions', 1);
    $version = AuditAssignmentVersion::query()->firstOrFail();
    expect($version->getRawOriginal('snapshot'))->not->toContain('original_dispatch_at')
        ->and($version->getRawOriginal('selection_basis'))->not->toContain('SYNTHETIC-CPA')
        ->and($version->toArray())->not->toHaveKeys(['snapshot', 'selection_basis', 'reason'])
        ->and($record->toArray())->not->toHaveKey('state');
});

it('accepts a current routine offer with a persisted 48 hour visit clock', function (): void {
    $fixture = Fixture::make();
    $record = Fixture::request($fixture, 'routine');
    $partner = Fixture::recipient($fixture, $record);
    $request = (string) Str::uuid();
    $receipt = Fixture::respond($partner['user'], $record, requestId: $request);
    expect($receipt['code'])->toBe('ASSIGNMENT_ACCEPTED')
        ->and(Fixture::respond($partner['user'], $record, requestId: $request))->toBe($receipt)
        ->and(app(FindAuditAssignmentOperation::class)->handle($partner['user']->id, 1, 'assignment.accept', $request))->toBe($receipt)
        ->and(app(GetAuditAssignment::class)->handle($partner['user']->id, 1, $record->id)['visit_by'])->toBe(now('UTC')->addHours(48)->format('Y-m-d\TH:i:s\Z'));
    expect($record->refresh()->state['complete_by'])->toBeNull()->and($record->status)->toBe('accepted');
});

it('atomically records a private conflict withdraws access and reoffers without resetting the deadline', function (): void {
    $fixture = Fixture::make();
    $record = Fixture::request($fixture);
    $partner = Fixture::recipient($fixture, $record);
    $deadline = $record->state['complete_by'];
    $request = (string) Str::uuid();
    $receipt = Fixture::respond($partner['user'], $record, 'conflict', 'Private family relationship.', 'family_or_business', $request);
    expect($receipt['code'])->toBe('CONFLICT_RECORDED')
        ->and(Fixture::respond($partner['user'], $record, 'conflict', 'Private family relationship.', 'family_or_business', $request))->toBe($receipt)
        ->and(app(FindAuditAssignmentOperation::class)->handle($partner['user']->id, 1, 'conflict.declare', $request))->toBe($receipt)
        ->and($record->refresh()->party_id)->not->toBe($partner['party']->id)
        ->and($record->state['complete_by'])->toBe($deadline)->and($record->state['attempt'])->toBe(2)
        ->and(json_encode($receipt, JSON_THROW_ON_ERROR))->not->toContain($record->party_id, 'Private family');
    expect(fn () => app(GetAuditAssignment::class)->handle($partner['user']->id, 1, $record->id))->toThrow(CommandRejection::class, 'ASSIGNMENT_NOT_FOUND');
    $conflict = AuditConflictDeclaration::query()->firstOrFail();
    expect($conflict->getRawOriginal('reason'))->not->toContain('Private family')->and($conflict->toArray())->not->toHaveKeys(['kind', 'reason']);
});

it('routes exhausted offers to Operations with the original Flash deadline', function (): void {
    $fixture = Fixture::make(4);
    $record = Fixture::request($fixture);
    $deadline = $record->state['complete_by'];
    for ($attempt = 1; $attempt <= 3; $attempt++) {
        $partner = Fixture::recipient($fixture, $record);
        expect(Fixture::respond($partner['user'], $record, 'decline', 'Cannot attend.')['code'])->toBe('ASSIGNMENT_DECLINED');
        $record->refresh();
    }
    expect($record->status)->toBe('operations')->and($record->party_id)->toBeNull()
        ->and($record->state['operations_reason'])->toBe('AUDIT_DISPATCH_EXHAUSTED')->and($record->state['complete_by'])->toBe($deadline);
});

it('advances an expired offer and cannot accept at the acceptance boundary', function (): void {
    $fixture = Fixture::make();
    $record = Fixture::request($fixture);
    $partner = Fixture::recipient($fixture, $record);
    $action = app(AdvanceAuditAssignment::class);
    expect($action->handle($fixture['staff']->id, $record->id, 1, (string) Str::uuid())['code'])->toBe('ASSIGNMENT_NOT_EXPIRED');
    $this->travel(1)->hours();
    expect(Fixture::respond($partner['user'], $record)['code'])->toBe('ASSIGNMENT_ACCEPTANCE_EXPIRED');
    expect($action->handle($fixture['staff']->id, $record->id, 1, (string) Str::uuid())['code'])->toBe('ASSIGNMENT_ADVANCED');
    expect($record->refresh()->state['attempt'])->toBe(2);
});

it('persists an Operations case when no candidate is eligible', function (): void {
    $fixture = Fixture::make(0);
    $record = Fixture::request($fixture);
    expect($record->status)->toBe('operations')->and($record->state['operations_reason'])->toBe('AUDIT_NO_ELIGIBLE_PARTNER');
    expect(app(RequestAuditAssignment::class)->handle($fixture['staff']->id, $fixture['business'], 'other', 'Request.', (string) Str::uuid())['code'])->toBe('AUDIT_CLOCK_INVALID');
});

it('rejects unauthorised staff and current identity changes before replay or lookup', function (): void {
    $fixture = Fixture::make(1);
    $partner = $fixture['partners'][0];
    expect(fn () => app(RequestAuditAssignment::class)->handle($partner['staff']->id, $fixture['business'], 'flash', 'Request.', (string) Str::uuid()))
        ->toThrow(IdentityViolation::class, 'STAFF_PERMISSION_REQUIRED');
    $record = Fixture::request($fixture);
    $request = (string) Str::uuid();
    Fixture::respond($partner['user'], $record, requestId: $request);
    RoleMembership::query()->where('party_id', $partner['party']->id)->update(['status' => 'suspended']);
    expect(fn () => Fixture::respond($partner['user'], $record, requestId: $request))->toThrow(IdentityViolation::class)
        ->and(fn () => app(FindAuditAssignmentOperation::class)->handle($partner['user']->id, 1, 'assignment.accept', $request))->toThrow(IdentityViolation::class);
});

it('does not expose previous recipients or permit another auditor to read or respond', function (): void {
    $fixture = Fixture::make();
    $record = Fixture::request($fixture);
    $partner = Fixture::recipient($fixture, $record);
    $other = array_values(array_filter($fixture['partners'], fn (array $entry): bool => $entry['party']->id !== $partner['party']->id))[0];
    expect(fn () => app(GetAuditAssignment::class)->handle($other['user']->id, 1, $record->id))->toThrow(CommandRejection::class, 'ASSIGNMENT_NOT_FOUND')
        ->and(fn () => Fixture::respond($other['user'], $record))->toThrow(CommandRejection::class, 'ASSIGNMENT_NOT_FOUND');
    $view = app(GetAuditAssignment::class)->handle($partner['user']->id, 1, $record->id);
    expect($view['allowed_actions'])->toContain('assignment.accept', 'assignment.decline', 'conflict.declare')
        ->and($view)->not->toHaveKeys(['state', 'tried', 'selection_basis', 'party_id']);
});

it('keeps expired offers private until the persisted redispatch takes place', function (): void {
    $fixture = Fixture::make(1);
    $record = Fixture::request($fixture);
    $this->travel(1)->hours();
    expect(fn () => app(GetAuditAssignment::class)->handle($fixture['partners'][0]['user']->id, 1, $record->id))->toThrow(CommandRejection::class, 'ASSIGNMENT_ACCEPTANCE_EXPIRED');
});

it('rechecks standing availability coordinates and reviewed independence on acceptance', function (string $change, string $code): void {
    $fixture = Fixture::make(1);
    $record = Fixture::request($fixture);
    $partner = $fixture['partners'][0];
    if ($change === 'review') {
        $review = AuditorIndependenceReview::query()->firstOrFail();
        $review->forceFill(['state' => [...$review->state, 'mandate_version' => 99]])->save();
    } elseif ($change === 'move') {
        app(MarkAuditLocationMoved::class)->handle($partner['staff']->id, 'office', $partner['party']->id, 1,
            now('UTC')->format('Y-m-d\TH:i:s\Z'), 'Office moved.', (string) Str::uuid());
    } else {
        $profile = AuditorProfile::query()->firstOrFail();
        $state = $profile->state;
        if ($change === 'pause') {
            $state['accepting'] = false;
        } else {
            $state['standing']['status'] = 'suspended';
        }
        $profile->forceFill(['state' => $state])->save();
    }
    expect(Fixture::respond($partner['user'], $record)['code'])->toBe($code)->and($record->refresh()->status)->toBe('offered');
    if (! in_array($change, ['standing', 'review'], true)) {
        expect(app(GetAuditAssignment::class)->handle($partner['user']->id, 1, $record->id)['allowed_actions'])->not->toContain('assignment.accept');
    }
})->with([['review', 'AUDITOR_INDEPENDENCE_REVIEW_REQUIRED'], ['move', 'AUDITOR_LOCATION_REVIEW_REQUIRED'], ['pause', 'AUDITOR_UNAVAILABLE'], ['standing', 'ACCREDITATION_SUSPENDED']]);

it('records conflicts after acceptance and when the Business identity or mandate has lapsed', function (string $change): void {
    $fixture = Fixture::make();
    $record = Fixture::request($fixture);
    $partner = Fixture::recipient($fixture, $record);
    Fixture::respond($partner['user'], $record);
    $record->refresh();
    if ($change === 'identity') {
        $fixture['authority']['people'][0]->forceFill(['verified_at' => null])->save();
    } else {
        $authority = $fixture['authority'];
        $authority['terms']['status'] = 'revoked';
        BusinessAuthorityFixture::configure($authority, 1);
    }
    $receipt = Fixture::respond($partner['user'], $record, 'conflict', 'New relationship found.', 'other');
    expect($receipt['code'])->toBe('CONFLICT_RECORDED')->and($record->refresh()->status)->toBe('operations')
        ->and(AuditConflictDeclaration::query()->count())->toBe(1);
})->with(['identity', 'mandate']);

it('excludes one invalid candidate without making the remaining pool unavailable', function (): void {
    $fixture = Fixture::make();
    $fixture['partners'][0]['party']->forceFill(['verified_at' => null])->save();
    $record = Fixture::request($fixture);
    expect($record->party_id)->toBe($fixture['partners'][1]['party']->id);
});

it('retains unresolved declarations even when a new staff review claims no conflict', function (): void {
    $fixture = Fixture::make(1);
    $record = Fixture::request($fixture);
    $partner = $fixture['partners'][0];
    Fixture::respond($partner['user'], $record, 'conflict', 'Financial interest found.', 'financial_interest');
    Fixture::independence($fixture['staff'], $fixture['business'], $partner['party']->id, 1);
    $version = AuditAssignmentVersion::query()->where('assignment_id', $record->id)->latest('revision')->firstOrFail();
    expect($version->snapshot['state']['status'])->toBe('operations');
    expect(Fixture::request($fixture)->status)->toBe('operations');
});

it('records changed revisions validation failures and duplicate bodies without changing the assignment', function (): void {
    $fixture = Fixture::make(1);
    $record = Fixture::request($fixture);
    $partner = $fixture['partners'][0];
    $request = (string) Str::uuid();
    $invalid = Fixture::respond($partner['user'], $record, 'conflict', 'Details.', 'invalid', $request);
    expect($invalid['code'])->toBe('AUDIT_CONFLICT_KIND_INVALID')
        ->and(Fixture::respond($partner['user'], $record, 'conflict', 'Details.', 'invalid', $request))->toBe($invalid)
        ->and(Fixture::respond($partner['user'], $record, 'decline')['code'])->toBe('ASSIGNMENT_REASON_REQUIRED');
    expect(fn () => Fixture::respond($partner['user'], $record, 'conflict', 'Changed.', 'invalid', $request))->toThrow(CommandRejection::class, 'IDEMPOTENCY_CONFLICT');
    $record->revision = 2;
    expect(Fixture::respond($partner['user'], $record)['code'])->toBe('VERSION_CONFLICT');
    expect(fn () => Fixture::respond($partner['user'], $record, 'invented'))->toThrow(CommandRejection::class, 'ASSIGNMENT_DECISION_INVALID');
    $this->assertDatabaseCount('audit_assignment_versions', 1);
    $this->assertDatabaseCount('audit_conflict_declarations', 0);
});

it('does not accept or decline an already accepted engagement under a new command', function (): void {
    $fixture = Fixture::make(1);
    $record = Fixture::request($fixture);
    $partner = $fixture['partners'][0];
    Fixture::respond($partner['user'], $record);
    $record->refresh();
    expect(Fixture::respond($partner['user'], $record)['code'])->toBe('ASSIGNMENT_NOT_OFFERED')
        ->and(Fixture::respond($partner['user'], $record, 'decline', 'Changed mind.')['code'])->toBe('ASSIGNMENT_NOT_OFFERED');
});

it('rolls back conflicts and reassignment when immutable history cannot be appended', function (): void {
    $fixture = Fixture::make();
    $record = Fixture::request($fixture);
    $partner = Fixture::recipient($fixture, $record);
    Event::listen('eloquent.creating: '.AuditAssignmentVersion::class, function (): void {
        throw new RuntimeException('History storage unavailable.');
    });
    try {
        expect(fn () => Fixture::respond($partner['user'], $record, 'conflict', 'Private evidence.', 'other'))->toThrow(RuntimeException::class, 'History storage unavailable.');
    } finally {
        Event::forget('eloquent.creating: '.AuditAssignmentVersion::class);
    }
    expect($record->refresh()->revision)->toBe(1)->and($record->party_id)->toBe($partner['party']->id);
    $this->assertDatabaseCount('audit_conflict_declarations', 0);
    $this->assertDatabaseCount('audit_assignment_versions', 1);
});

it('refuses unsupported and unknown operation lookups and records', function (): void {
    $fixture = Fixture::make(1);
    $user = $fixture['partners'][0]['user'];
    expect(fn () => app(FindAuditAssignmentOperation::class)->handle($user->id, 1, 'accreditation.submit', (string) Str::uuid()))->toThrow(CommandRejection::class, 'OPERATION_NOT_FOUND')
        ->and(fn () => app(FindAuditAssignmentOperation::class)->handle($user->id, 1, 'assignment.accept', (string) Str::uuid()))->toThrow(CommandRejection::class, 'OPERATION_NOT_FOUND')
        ->and(fn () => app(GetAuditAssignment::class)->handle($user->id, 1, 'missing'))->toThrow(CommandRejection::class, 'ASSIGNMENT_NOT_FOUND');
});

it('makes assignment history and conflicts immutable and protects mass assignment', function (): void {
    $fixture = Fixture::make(1);
    $record = Fixture::request($fixture);
    Fixture::respond($fixture['partners'][0]['user'], $record, 'conflict', 'Financial conflict.', 'financial_interest');
    foreach (['audit_assignment_versions', 'audit_conflict_declarations'] as $table) {
        expect(fn () => DB::transaction(fn (): int => DB::table($table)->delete()))->toThrow(QueryException::class)
            ->and(fn () => DB::transaction(fn (): int => DB::table($table)->update(['policy_version' => 'changed'])))->toThrow(QueryException::class);
    }
    foreach ([AuditAssignment::class, AuditAssignmentVersion::class, AuditConflictDeclaration::class] as $model) {
        expect(fn () => new $model(['revision' => 999]))->toThrow(MassAssignmentException::class);
    }
});

it('reverses and reapplies only the assignment schema on the isolated test database', function (): void {
    $migration = require database_path('migrations/2026_09_24_113400_create_audit_assignments_and_conflicts.php');
    $verification = require database_path('migrations/2026_09_24_124527_create_statement_verifications_table.php');
    $verification->down();
    $migration->down();
    expect(Schema::hasTable('audit_assignments'))->toBeFalse();
    $migration->up();
    expect(Schema::hasTable('audit_conflict_declarations'))->toBeTrue();
    $verification->up();
});

it('automatically reoffers expired jobs with system history and without impersonating a staff user', function (): void {
    DB::statement("SET LOCAL TIME ZONE 'Asia/Qatar'");
    $fixture = Fixture::make();
    $record = Fixture::request($fixture);
    $deadline = $record->state['complete_by'];
    $journalCount = CommandOperation::query()->count();
    expect(app(AdvanceExpiredAuditOffers::class)->handle(100))->toBe(0);
    $this->travel(1)->hours();
    expect(Artisan::call('audits:advance-offers', ['--limit' => '1']))->toBe(0);
    expect(Artisan::output())->toContain('Advanced 1 expired audit offers.');
    expect($record->refresh()->state['attempt'])->toBe(2)->and($record->state['complete_by'])->toBe($deadline)
        ->and(CommandOperation::query()->count())->toBe($journalCount)
        ->and(AuditAssignmentVersion::query()->where('assignment_id', $record->id)->where('revision', 2)->value('actor_user_id'))->toBeNull()
        ->and(app(AdvanceExpiredAuditOffers::class)->handle(100))->toBe(0);
    $this->travel(24)->hours();
    expect(app(AdvanceExpiredAuditOffers::class)->handle(100))->toBe(1)
        ->and($record->refresh()->status)->toBe('operations')->and($record->state['complete_by'])->toBe($deadline);
    expect(Artisan::call('audits:advance-offers', ['--limit' => '1.5']))->toBe(2);
    expect(fn () => app(AdvanceExpiredAuditOffers::class)->handle(0))->toThrow(CommandRejection::class, 'AUDIT_BATCH_LIMIT_INVALID');
});

it('prefers lower actual engagement load and enforces the three-engagement ceiling at acceptance', function (): void {
    $fixture = Fixture::make(1);
    $party = $fixture['partners'][0]['party']->id;
    $record = Fixture::request($fixture);
    for ($index = 0; $index < 3; $index++) {
        Fixture::engagement($party);
    }
    expect(Fixture::respond($fixture['partners'][0]['user'], $record)['code'])->toBe('AUDITOR_CAPACITY_REACHED');
    expect(app(GetAuditAssignment::class)->handle($fixture['partners'][0]['user']->id, 1, $record->id)['allowed_actions'])->not->toContain('assignment.accept');
});

it('rotates after three consecutive completed engagements and restarts the count after another partner', function (bool $interrupted): void {
    $fixture = Fixture::make(2);
    $first = $fixture['partners'][0]['party']->id;
    $second = $fixture['partners'][1]['party']->id;
    for ($index = 0; $index < 3; $index++) {
        Fixture::engagement($first, 'completed', $fixture['business']);
        $this->travel(1)->seconds();
    }
    if ($interrupted) {
        Fixture::engagement($second, 'completed', $fixture['business']);
        $this->travel(1)->seconds();
    }
    $record = Fixture::request($fixture);
    if ($interrupted) {
        $ids = [$first, $second];
        sort($ids);
        expect($record->party_id)->toBe($ids[0]);
    } else {
        expect($record->party_id)->toBe($second);
    }
})->with([false, true]);

it('retains mandate relationship history after removing a person and obtaining a new review', function (): void {
    $fixture = Fixture::make(1, 'organization');
    $partner = $fixture['partners'][0];
    $authority = $fixture['authority'];
    $authority['terms']['people'][] = ['party_id' => $partner['party']->id, 'name' => 'Related person', 'roles' => ['director'], 'permissions' => ['business.view']];
    BusinessAuthorityFixture::configure($authority, 1);
    $this->travel(1)->seconds();
    BusinessAuthorityFixture::configure($fixture['authority'], 2);
    Fixture::independence($fixture['staff'], $fixture['business'], $partner['party']->id, 1);
    expect(Fixture::request($fixture)->status)->toBe('operations');
});

it('withdraws private access when a later current independence review finds a conflict', function (): void {
    $fixture = Fixture::make(1);
    $record = Fixture::request($fixture);
    $partner = $fixture['partners'][0];
    Fixture::respond($partner['user'], $record);
    $facts = [...AuditorIndependenceFixture::facts(), 'financial_interest' => true];
    app(RecordAuditorIndependence::class)->handle($fixture['staff']->id, $fixture['business'], $partner['party']->id, 1,
        $facts, now('UTC')->format('Y-m-d\TH:i:s\Z'), 'new:interest-evidence', 'Current interest found.', (string) Str::uuid());
    expect(fn () => app(GetAuditAssignment::class)->handle($partner['user']->id, 1, $record->id))->toThrow(CommandRejection::class, 'AUDITOR_INDEPENDENCE_REVIEW_REQUIRED');
});

it('enforces the one-open-engagement and completion invariants in PostgreSQL', function (): void {
    $fixture = Fixture::make(1);
    $record = Fixture::request($fixture);
    expect(fn () => DB::transaction(fn () => AuditAssignment::factory()->create(['business_id' => $fixture['business']])))->toThrow(QueryException::class)
        ->and(fn () => DB::transaction(fn () => $record->forceFill(['status' => 'completed'])->save()))->toThrow(QueryException::class)
        ->and(fn () => DB::transaction(fn () => $record->forceFill(['revision' => 0])->save()))->toThrow(QueryException::class);
});

it('refuses an invalid Business or newly unverified authority before creating or accepting an offer', function (): void {
    $fixture = Fixture::make(1);
    expect(fn () => app(RequestAuditAssignment::class)->handle($fixture['staff']->id, 'missing', 'flash', 'Request.', (string) Str::uuid()))->toThrow(CommandRejection::class, 'BUSINESS_NOT_FOUND');
    $record = Fixture::request($fixture);
    $fixture['authority']['people'][0]->forceFill(['verified_at' => null])->save();
    expect(fn () => Fixture::respond($fixture['partners'][0]['user'], $record))->toThrow(IdentityViolation::class, 'PARTY_AUTHORITY_REQUIRED');
    $fixture['authority']['people'][0]->forceFill(['verified_at' => now()])->save();
    $authority = $fixture['authority'];
    $authority['terms']['status'] = 'revoked';
    BusinessAuthorityFixture::configure($authority, 1);
    expect(fn () => Fixture::respond($fixture['partners'][0]['user'], $record))->toThrow(CommandRejection::class, 'MANDATE_REQUIRED');
});

it('does not grant an anonymous worker a participant context', function (): void {
    $fixture = Fixture::make(0);
    expect(fn () => app(IdentityAccessStore::class)->withAuditAccess(null, 1, 'person', $fixture['authority']['entity'],
        [$fixture['authority']['entity']], [], false, fn (): bool => true))->toThrow(IdentityViolation::class, 'ACTIVE_ROLE_REQUIRED');
});

it('does not redispatch a partner who already declined this engagement', function (): void {
    $states = app(AuditEngagementState::class);
    $time = now()->toDateTimeImmutable();
    $state = $states->offer($states->start('flash', $time), 'partner-a', $time);
    expect(fn () => $states->offer($state, 'partner-a', $time))->toThrow(CommandRejection::class, 'AUDIT_PARTNER_ALREADY_OFFERED');
});

it('does not return a receipt belonging to another target type', function (): void {
    $fixture = Fixture::make(1);
    $partner = $fixture['partners'][0];
    $request = (string) Str::uuid();
    app(OperationJournal::class)->execute('party:'.$partner['party']->id, $partner['user']->id,
        'assignment.accept', $request, 'other.target', 'fixture', [], function (): void {}, fn (): OperationResult => new OperationResult('SYNTHETIC', [], 1));
    expect(fn () => app(FindAuditAssignmentOperation::class)->handle($partner['user']->id, 1, 'assignment.accept', $request))->toThrow(CommandRejection::class, 'OPERATION_NOT_FOUND');
});

it('rejects a receipt when identity relinking wins before the locked authorization check', function (): void {
    $fixture = Fixture::make(1);
    $record = Fixture::request($fixture);
    $partner = $fixture['partners'][0];
    $other = AuditorFixture::make();
    $request = (string) Str::uuid();
    Fixture::respond($partner['user'], $record, requestId: $request);
    Event::listen('eloquent.retrieved: '.CommandOperation::class, function () use ($partner, $other): void {
        $partner['user']->forceFill(['party_id' => $other['party']->id, 'context_revision' => 2,
            'active_membership_id' => $other['user']->refresh()->active_membership_id, 'active_membership_revision' => 1])->save();
    });
    try {
        expect(fn () => app(FindAuditAssignmentOperation::class)->handle($partner['user']->id, 2, 'assignment.accept', $request))->toThrow(CommandRejection::class, 'OPERATION_NOT_FOUND');
    } finally {
        Event::forget('eloquent.retrieved: '.CommandOperation::class);
    }
});

it('skips an expired scan result already advanced by another worker before its row lock', function (): void {
    $fixture = Fixture::make();
    $record = Fixture::request($fixture);
    $this->travel(1)->hours();
    $advanced = false;
    Event::listen('eloquent.retrieved: '.AuditAssignment::class, function () use (&$advanced): void {
        if (! $advanced) {
            $advanced = true;
            app(AdvanceExpiredAuditOffers::class)->handle(100);
        }
    });
    try {
        expect(app(AdvanceExpiredAuditOffers::class)->handle(100))->toBe(0);
    } finally {
        Event::forget('eloquent.retrieved: '.AuditAssignment::class);
    }
    expect($record->refresh()->state['attempt'])->toBe(2)->and(AuditAssignmentVersion::query()->count())->toBe(2);
});
