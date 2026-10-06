<?php

declare(strict_types=1);

use App\Application\Business\WithBusinessAuthority;
use App\Application\Evidence\WithBusinessStatementVerification;
use App\Domain\Identity\IdentityViolation;
use App\Domain\Operations\CommandRejection;
use App\Models\Party;
use App\Models\RoleMembership;
use App\Models\StatementVerification;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\Support\AuditAssignmentFixture as Fixture;
use Tests\Support\AuditorFixture;
use Tests\Support\BusinessAuthorityFixture;

beforeEach(function (): void {
    $this->freezeTime();
});

it('holds the actual Business context and immutable verification through a protected calculation', function (): void {
    $fixture = Fixture::make(1);
    $sources = Fixture::statements($fixture);
    $assignment = Fixture::request($fixture);
    Fixture::respond($fixture['partners'][0]['user'], $assignment);
    $receipt = Fixture::verifyStatements($fixture, $assignment->refresh(), $sources['transcription_id']);
    $owner = $fixture['authority']['users'][0];
    $result = app(WithBusinessStatementVerification::class)->handle($owner->id, 1, $fixture['business'], 'application.evaluate', 1,
        function (array $business, array $identity, ?array $verification) use ($fixture, $owner, $receipt): string {
            expect($business['id'])->toBe($fixture['business'])->and($business['mandate_version'])->toBe(1)
                ->and($identity['party']['id'])->toBe($owner->party_id)
                ->and($verification['id'])->toBe($receipt['data']['verification_id'])
                ->and($verification['current'])->toBeTrue()
                ->and($verification['sha256'])->toBe($receipt['data']['sha256'])
                ->and(DB::transactionLevel())->toBeGreaterThan(1);

            return $verification['sha256'];
        });
    expect($result)->toBe($receipt['data']['sha256']);
});

it('keeps missing source verification explicit instead of inventing financial inputs', function (): void {
    $fixture = Fixture::make(0);
    expect(app(WithBusinessStatementVerification::class)->handle($fixture['authority']['users'][0]->id, 1, $fixture['business'], 'application.evaluate', null,
        fn (array $business, array $identity, ?array $verification): ?array => $verification))->toBeNull();
});

it('allows a recorded refusal to observe withdrawn source authority without treating the Auditor as a Business signatory', function (string $change): void {
    $fixture = Fixture::make(1);
    $sources = Fixture::statements($fixture);
    $assignment = Fixture::request($fixture);
    $partner = $fixture['partners'][0];
    Fixture::respond($partner['user'], $assignment);
    Fixture::verifyStatements($fixture, $assignment->refresh(), $sources['transcription_id']);
    if ($change === 'identity') {
        $partner['party']->forceFill(['verified_at' => null])->save();
    } elseif ($change === 'membership') {
        RoleMembership::query()->where('party_id', $partner['party']->id)->where('role', 'auditor')->update(['status' => 'revoked']);
    } else {
        AuditorFixture::review($partner['staff'], $partner['party']->id, 3, 'suspend');
    }

    expect(app(WithBusinessStatementVerification::class)->handle($fixture['authority']['users'][0]->id, 1, $fixture['business'], 'application.evaluate', 1,
        fn (array $business, array $identity, ?array $verification): bool => $verification !== null && $verification['current']))->toBeFalse();
})->with(['identity', 'membership', 'standing']);

it('does not expand Business permissions when a source author is included in the lock set', function (): void {
    $fixture = Fixture::make(1);
    $owner = $fixture['authority']['users'][0];
    $partner = $fixture['partners'][0];
    $guard = app(WithBusinessAuthority::class);
    expect(fn () => $guard->handle($partner['user']->id, 1, $fixture['business'], 'application.evaluate', 1,
        fn (): never => throw new RuntimeException('Unauthorized callback executed.'), [$partner['party']->id]))
        ->toThrow(CommandRejection::class, 'BUSINESS_NOT_FOUND');
    $authority = $fixture['authority'];
    $authority['terms']['people'] = array_map(fn (array $person): array => [...$person, 'permissions' => ['business.view', 'application.sign', 'report.cosign']], $authority['terms']['people']);
    BusinessAuthorityFixture::configure($authority, 1);
    expect(fn () => $guard->handle($owner->id, 1, $fixture['business'], 'application.evaluate', 2,
        fn (): never => throw new RuntimeException('Unauthorized callback executed.'), [$partner['party']->id]))
        ->toThrow(CommandRejection::class, 'ACTION_FORBIDDEN');
});

it('denies stale Business context and mandate versions before performing a calculation', function (): void {
    $fixture = Fixture::make(0);
    $guard = app(WithBusinessStatementVerification::class);
    $owner = $fixture['authority']['users'][0];
    $never = fn (): never => throw new RuntimeException('Stale callback executed.');
    expect(fn () => $guard->handle($owner->id, 0, $fixture['business'], 'application.evaluate', 1, $never))
        ->toThrow(IdentityViolation::class, 'ACTIVE_ROLE_REVISION_CONFLICT')
        ->and(fn () => $guard->handle($owner->id, 1, $fixture['business'], 'application.evaluate', 2, $never))
        ->toThrow(CommandRejection::class, 'MANDATE_STALE');
});

it('rolls back the calculation effect when its protected transaction fails', function (): void {
    $fixture = Fixture::make(0);
    DB::table('signup_counters')->insert(['name' => 'underwriting-effect', 'value' => 0]);
    expect(fn () => app(WithBusinessStatementVerification::class)->handle($fixture['authority']['users'][0]->id, 1, $fixture['business'], 'application.evaluate', 1,
        function (): never {
            DB::table('signup_counters')->where('name', 'underwriting-effect')->increment('value');
            throw new RuntimeException('Calculation failed.');
        }))->toThrow(RuntimeException::class, 'Calculation failed.');
    expect(DB::table('signup_counters')->where('name', 'underwriting-effect')->value('value'))->toBe(0);
});

it('rejects an author change between discovering the lock set and obtaining Business authority', function (bool $existing): void {
    $fixture = Fixture::make(2);
    $sources = Fixture::statements($fixture);
    $assignment = Fixture::request($fixture);
    $former = Fixture::recipient($fixture, $assignment);
    Fixture::respond($former['user'], $assignment);
    $assignment->refresh();
    if ($existing) {
        Fixture::verifyStatements($fixture, $assignment, $sources['transcription_id']);
    }
    $changed = false;
    Event::listen(QueryExecuted::class, function (QueryExecuted $query) use (&$changed, $fixture, $assignment, $sources, $former, $existing): void {
        if ($changed || ! str_starts_with($query->sql, 'select "actor_party_id" from "statement_verifications"')) {
            return;
        }
        $changed = true;
        if ($existing) {
            Fixture::respond($former['user'], $assignment, 'conflict', 'New financial interest.', 'financial_interest');
            $assignment->refresh();
            Fixture::respond(Fixture::recipient($fixture, $assignment)['user'], $assignment);
            $assignment->refresh();
        }
        Fixture::verifyStatements($fixture, $assignment, $sources['transcription_id'], verificationRevision: $existing ? 1 : 0);
    });

    expect(fn () => app(WithBusinessStatementVerification::class)->handle($fixture['authority']['users'][0]->id, 1, $fixture['business'], 'application.evaluate', 1,
        fn (): never => throw new RuntimeException('Unlocked author reached the calculation.')))->toThrow(CommandRejection::class, 'VERSION_CONFLICT');
    expect($changed)->toBeTrue();
})->with(['replacement author' => true, 'first verification' => false]);

it('locks all required and source-author parties together in stable order without locking unrelated parties', function (): void {
    $fixture = Fixture::make(1, 'organization');
    $sources = Fixture::statements($fixture);
    $assignment = Fixture::request($fixture);
    Fixture::respond($fixture['partners'][0]['user'], $assignment);
    Fixture::verifyStatements($fixture, $assignment->refresh(), $sources['transcription_id']);
    $unrelated = Party::factory()->verified()->create();
    $queries = [];
    Event::listen(QueryExecuted::class, function (QueryExecuted $query) use (&$queries): void {
        if (str_contains($query->sql, 'from "parties"') && str_contains($query->sql, 'for update')) {
            $queries[] = $query;
        }
    });
    app(WithBusinessStatementVerification::class)->handle($fixture['authority']['users'][0]->id, 1, $fixture['business'], 'application.evaluate', 1, fn (): bool => true);
    expect($queries[0]->sql)->toContain('order by "id" asc for update')
        ->and($queries[0]->bindings)->toEqualCanonicalizing([$fixture['authority']['entity'], $fixture['authority']['people'][0]->id, $fixture['partners'][0]['party']->id])
        ->and($queries[0]->bindings)->not->toContain($unrelated->id);
});

it('does not invoke a calculation when immutable source integrity fails', function (): void {
    $fixture = Fixture::make(1);
    $sources = Fixture::statements($fixture);
    $assignment = Fixture::request($fixture);
    Fixture::respond($fixture['partners'][0]['user'], $assignment);
    Fixture::verifyStatements($fixture, $assignment->refresh(), $sources['transcription_id']);
    $event = 'eloquent.retrieved: '.StatementVerification::class;
    Event::listen($event, function (StatementVerification $record): void {
        $record->sha256 = str_repeat('0', 64);
    });
    try {
        expect(fn () => app(WithBusinessStatementVerification::class)->handle($fixture['authority']['users'][0]->id, 1, $fixture['business'], 'application.evaluate', 1,
            fn (): never => throw new RuntimeException('Corrupt source reached the calculation.')))->toThrow(RuntimeException::class, 'STATEMENT_VERIFICATION_INTEGRITY_FAILED');
    } finally {
        Event::forget($event);
    }
    expect(StatementVerification::query()->count())->toBe(1);
});
