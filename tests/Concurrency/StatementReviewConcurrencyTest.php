<?php

declare(strict_types=1);

use App\Application\Auditor\Contracts\AuditAssignmentStore;
use App\Application\Evidence\GetStaffStatementAuditReadiness;
use App\Application\Evidence\RequestStatementReview;
use App\Domain\Identity\IdentityViolation;
use App\Domain\Operations\CommandRejection;
use App\Models\AuditAssignment;
use App\Models\StatementVerification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\AuditAssignmentFixture as Fixture;

/**
 * Runs each contender in its own process and connection. Exit 0 means the call returned (a journaled
 * outcome, which may itself be a refusal), 2 a thrown domain refusal and 1 any other failure.
 *
 * @param  list<Closure(): mixed>  $operations
 * @return list<int>
 */
function statementReviewContenders(array $operations): array
{
    $pids = [];
    foreach ($operations as $operation) {
        $pid = pcntl_fork();
        if ($pid === -1) {
            throw new RuntimeException('Could not fork a statement review contender.');
        }
        if ($pid === 0) {
            DB::purge();
            try {
                $operation();
                exit(0);
            } catch (IdentityViolation|CommandRejection) {
                exit(2);
            } catch (Throwable) {
                exit(1);
            }
        }
        $pids[] = $pid;
    }
    $statuses = [];
    foreach ($pids as $pid) {
        pcntl_waitpid($pid, $status);
        $statuses[] = pcntl_wifexited($status) ? pcntl_wexitstatus($status) : 1;
    }

    return $statuses;
}

/**
 * @param  array<string, mixed>  $fixture
 * @return Closure(): array<string, mixed>
 */
function statementReviewRequest(array $fixture): Closure
{
    $readiness = app(GetStaffStatementAuditReadiness::class)->handle($fixture['staff']->id, $fixture['business']);
    $binding = ['expected_business_revision' => $readiness['business_revision'], 'expected_mandate_version' => $readiness['mandate_version'],
        'expected_evidence_revision' => $readiness['evidence_revision'], 'transcription_id' => $readiness['transcription']['id'],
        'transcription_sha256' => $readiness['transcription']['sha256'], 'readiness_sha256' => $readiness['readiness_sha256']];

    return fn (): array => app(RequestStatementReview::class)->handle($fixture['staff']->id, $fixture['business'], $binding, 'Factual review.', (string) Str::uuid());
}

it('opens one statement review when two staff requests race', function (): void {
    $fixture = Fixture::make(1);
    Fixture::statements($fixture);
    $request = statementReviewRequest($fixture);

    expect(statementReviewContenders([$request, $request]))->toBe([0, 0])
        ->and(AuditAssignment::query()->where('business_id', $fixture['business'])->count())->toBe(1)
        ->and(AuditAssignment::query()->sole()->state['purpose'])->toBe('statement_review');
});

it('opens exactly one engagement when a review request races the monthly run', function (): void {
    $fixture = Fixture::make(1);
    Fixture::statements($fixture);
    $request = statementReviewRequest($fixture);

    expect(statementReviewContenders([$request, fn (): ?string => app(AuditAssignmentStore::class)->requestMonthly($fixture['business'])]))->toBe([0, 0])
        ->and(AuditAssignment::query()->where('business_id', $fixture['business'])->whereIn('status', ['offered', 'accepted', 'operations'])->count())->toBe(1);
});

it('never completes a review from a partner whose conflict won the race against the verification', function (): void {
    $fixture = Fixture::make(1);
    $sources = Fixture::statements($fixture);
    $partner = $fixture['partners'][0];
    $assignment = AuditAssignment::query()->findOrFail(statementReviewRequest($fixture)()['data']['assignment_id']);
    Fixture::respond($partner['user'], $assignment);
    $assignment->refresh();

    expect(statementReviewContenders([
        fn (): array => Fixture::verifyStatements($fixture, $assignment, $sources['transcription_id']),
        fn (): array => Fixture::respond($partner['user'], $assignment, 'conflict', 'New financial interest.', 'financial_interest'),
    ]))->each->toBeIn([0, 2]);
    $assignment->refresh();
    $verified = StatementVerification::query()->exists();
    // Whichever committed first decides: a completed review has its verification; a conflicted one has none and is not completed.
    expect($assignment->status === 'completed')->toBe($verified && $assignment->party_id === $partner['party']->id)
        ->and($assignment->status)->toBeIn(['completed', 'operations', 'offered']);
});
