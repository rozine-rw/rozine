<?php

declare(strict_types=1);

use App\Application\Disbursement\ClosingEvidence;
use App\Application\Disbursement\Contracts\DisbursementClosingEvidence;
use App\Application\Disbursement\Contracts\FundedCampaigns;
use App\Application\Disbursement\FailedClosing;
use App\Application\Disbursement\FundedCampaign;
use App\Application\Disbursement\IssueInstruction;
use App\Application\Disbursement\RecheckResult;
use App\Application\Disbursement\RecordPayoutEvent;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Domain\Disbursement\DisbursementViolation;
use App\Infrastructure\Disbursement\SyntheticDisbursementSources;
use App\Models\Disbursement;
use App\Models\DisbursementClosing;
use App\Models\DisbursementIntent;
use App\Models\DisbursementReconciliation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\DisbursementFixture;

/*
 * The read-only closing evidence a funding source issues or fails closing against (#96
 * 5925426310): every cause through the real store flows, read both from inside `issue`/`failClose`
 * and afterwards, and each stored contradiction refused. Corruptions lift the append-only trigger
 * (and, where the schema would refuse the shape outright, its constraint) inside the test's
 * rolled-back transaction.
 */

/** @return ArrayObject<int, array{instruction: IssueInstruction|FailedClosing, evidence: ClosingEvidence}> */
function closingCalls(): ArrayObject
{
    return new ArrayObject;
}

/**
 * A funding source that delegates to the synthetic one and reads the closing evidence inside every closing call.
 *
 * @param  ArrayObject<int, array{instruction: IssueInstruction|FailedClosing, evidence: ClosingEvidence}>  $seen
 */
function evidenceRecordingFunding(ArrayObject $seen): FundedCampaigns
{
    return new class(app(SyntheticDisbursementSources::class), $seen) implements FundedCampaigns
    {
        /** @param ArrayObject<int, array{instruction: IssueInstruction|FailedClosing, evidence: ClosingEvidence}> $seen */
        public function __construct(private FundedCampaigns $inner, private ArrayObject $seen) {}

        public function funded(?string $before, int $limit): array
        {
            return $this->inner->funded($before, $limit);
        }

        public function lockBusiness(string $businessId): void
        {
            $this->inner->lockBusiness($businessId);
        }

        public function lockFunded(string $campaignId): FundedCampaign
        {
            return $this->inner->lockFunded($campaignId);
        }

        public function recheck(FundedCampaign $campaign): RecheckResult
        {
            return $this->inner->recheck($campaign);
        }

        public function issue(FundedCampaign $campaign, IssueInstruction $instruction): void
        {
            $this->seen[] = ['instruction' => $instruction, 'evidence' => app(DisbursementClosingEvidence::class)->find($instruction->closingId)];
            $this->inner->issue($campaign, $instruction);
        }

        public function failClose(FundedCampaign $campaign, FailedClosing $closing): void
        {
            $this->seen[] = ['instruction' => $closing, 'evidence' => app(DisbursementClosingEvidence::class)->find($closing->closingId)];
            $this->inner->failClose($campaign, $closing);
        }
    };
}

/** The evidence as a caller reads it inside its own transaction. */
function closingEvidence(string $closingId): ClosingEvidence
{
    return DB::transaction(fn (): ClosingEvidence => app(DisbursementClosingEvidence::class)->find($closingId));
}

/** A real closing for one cause, through the store's own flows. */
function closingFor(string $cause): DisbursementClosing
{
    if ($cause === 'approve_recheck' || $cause === 'worker_recheck') {
        ['campaign' => $campaign, 'disbursement' => $disbursement] = DisbursementFixture::funded();
        $maker = DisbursementFixture::staff(['treasury']);
        $checker = DisbursementFixture::staff(['approver']);
        DisbursementFixture::command($maker, $disbursement, 'authorize', 0);
        $proof = DisbursementFixture::stepUp($checker, $disbursement)['proof'];
        if ($cause === 'approve_recheck') {
            DisbursementFixture::sources()->scriptRecheck($campaign->campaignId, 'failed', ['restriction']);
            DisbursementFixture::command($checker, $disbursement, 'approve', 1, $proof);
        } else {
            // The approve recheck passes; the worker's fresh recheck after commit fails.
            DB::transaction(function () use ($checker, $disbursement, $proof, $campaign): void {
                DisbursementFixture::command($checker, $disbursement, 'approve', 1, $proof);
                DisbursementFixture::sources()->scriptRecheck($campaign->campaignId, 'failed', ['exposure']);
            });
        }

        return DisbursementClosing::query()->where('disbursement_id', $disbursement->id)->sole();
    }
    ['intent' => $intent] = DisbursementFixture::approved();
    app(RecordPayoutEvent::class)->handle(DisbursementFixture::provider()->callback($intent->id, $cause === 'reconciled_success' ? 'succeeded' : 'failed',
        ['effective_at' => '2028-01-31T08:00:00+00:00']));

    return DisbursementClosing::query()->where('intent_id', $intent->id)->sole();
}

/**
 * Runs a change with one table's append-only trigger lifted; the test's transaction rolls it back.
 * Deferred keys are checked first, since PostgreSQL refuses DDL on a table with pending trigger events.
 */
function withTriggerLifted(string $table, string $trigger, Closure $change): void
{
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement("ALTER TABLE {$table} DISABLE TRIGGER {$trigger}");
    try {
        $change();
    } finally {
        DB::statement("ALTER TABLE {$table} ENABLE TRIGGER {$trigger}");
    }
}

/** Drops one check constraint for the rest of the test's rolled-back transaction. */
function withoutConstraint(string $table, string $constraint): void
{
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement("ALTER TABLE {$table} DROP CONSTRAINT {$constraint}");
}

/**
 * Rewrites a record's columns and payload fields, then recomputes its digest so only the
 * contradiction under test remains.
 *
 * @param  array<string, mixed>  $columns
 * @param  array<string, mixed>  $payload
 */
function rewritten(Model $record, string $trigger, array $columns, array $payload = []): void
{
    $merged = [...$record->getAttribute('payload'), ...$payload];
    withTriggerLifted($record->getTable(), $trigger, fn () => $record->forceFill([...$columns, 'payload' => $merged,
        'sha256' => hash('sha256', app(CanonicalJson::class)->encode($merged))])->save());
}

it('authenticates every cause inside the closing call and afterwards, with the retained issue instant', function (string $cause): void {
    $seen = closingCalls();
    app()->instance(FundedCampaigns::class, evidenceRecordingFunding($seen));
    // Time moves on between recording the closing and issuing, so only the retained instant matches.
    DisbursementClosing::saved(fn () => $this->travel(1)->hours());
    $closing = closingFor($cause);
    $disbursement = Disbursement::query()->findOrFail($closing->disbursement_id);
    $intent = DisbursementIntent::query()->where('disbursement_id', $disbursement->id)->first();
    $evidence = closingEvidence($closing->id);
    ['instruction' => $instruction, 'evidence' => $inside] = $seen[0];
    $issued = $cause === 'reconciled_success';

    expect($seen)->toHaveCount(1)->and($inside)->toEqual($evidence)
        ->and([$evidence->closingId, $evidence->kind, $evidence->cause, $evidence->causes])->toBe([$closing->id, $issued ? 'issued' : 'failed_closing', $cause,
            ['approve_recheck' => ['restriction'], 'worker_recheck' => ['exposure']][$cause] ?? []])
        ->and([$evidence->disbursementId, $evidence->campaignId, $evidence->businessId, $evidence->exposureReservationId, $evidence->amount, $evidence->currency,
            $evidence->commitmentsDigest, $evidence->commitmentCount, $evidence->termMonths, $evidence->disbursementSha256])
        ->toBe([$disbursement->id, $disbursement->business_campaign_id, $disbursement->business_id, $disbursement->exposure_reservation_id, $disbursement->amount,
            'RWF', $disbursement->commitments_digest, 2, 12, $disbursement->sha256])
        ->and([$evidence->intentId, $evidence->intentOperationId, $evidence->reconciliationId, $evidence->operationId])
        ->toBe([$intent?->id, $intent?->operation_id, $closing->reconciliation_id, $closing->operation_id])
        ->and([$evidence->recordedAt, $evidence->closingSha256])->toBe([$closing->payload['recorded_at'], $closing->sha256])
        ->and($instruction->closingId)->toBe($closing->id);
    expect($instruction instanceof IssueInstruction)->toBe($issued);
    if ($instruction instanceof IssueInstruction) {
        expect([$evidence->effectiveAt, $evidence->effectiveDate, $evidence->dueDates])
            ->toBe([$instruction->effectiveAt, $instruction->effectiveDate, $instruction->dueDates])
            ->and([$evidence->effectiveAt, $evidence->effectiveDate, array_slice($evidence->dueDates ?? [], 0, 2)])
            ->toBe(['2028-01-31T08:00:00+00:00', '2028-01-31', ['2028-02-29', '2028-03-31']])
            ->and($instruction->issuedAt)->toBe($evidence->recordedAt)
            ->and($closing->payload['effective_at'])->toBe('2028-01-31T08:00:00+00:00');
    } else {
        expect([$instruction->cause, $instruction->causes])->toBe([$evidence->cause, $evidence->causes])
            ->and([$evidence->effectiveAt, $evidence->effectiveDate, $evidence->dueDates])->toBe([null, null, null])
            ->and($closing->payload['effective_at'])->toBeNull();
    }
})->with(['approve_recheck', 'worker_recheck', 'reconciled_failure', 'reconciled_success']);

it('refuses outside a transaction the caller opened', function (): void {
    $closing = closingFor('reconciled_failure');

    expect(fn () => app(DisbursementClosingEvidence::class)->find($closing->id))
        ->toThrow(DisbursementViolation::class, 'DISBURSEMENT_CLOSING_TRANSACTION_REQUIRED');
});

it('reads with plain SELECTs only: no row locks and no writes', function (string $cause): void {
    $closing = closingFor($cause);
    $statements = [];
    DB::listen(function (QueryExecuted $query) use (&$statements): void {
        $statements[] = strtolower($query->sql);
    });
    closingEvidence($closing->id);
    $reads = array_values(array_filter($statements, fn (string $sql): bool => ! str_starts_with($sql, 'savepoint') && ! str_starts_with($sql, 'release savepoint')));

    expect($reads)->not->toBeEmpty()
        ->and(array_filter($reads, fn (string $sql): bool => ! str_starts_with($sql, 'select ') || preg_match('/\bfor (update|share|no key update|key share)\b/', $sql) === 1))
        ->toBe([]);
})->with(['approve_recheck', 'reconciled_success']);

it('is unavailable for an unknown closing or an unreadable retained payload', function (string $case): void {
    $closing = closingFor('reconciled_success');
    if ($case === 'unreadable closing payload') {
        withTriggerLifted('disbursement_closings', 'disbursement_closings_protected',
            fn () => DB::table('disbursement_closings')->where('id', $closing->id)->update(['payload' => 'not-a-ciphertext']));
    } elseif ($case === 'unreadable intent payload') {
        withTriggerLifted('disbursement_intents', 'disbursement_intents_protected',
            fn () => DB::table('disbursement_intents')->where('id', $closing->intent_id)->update(['payload' => 'not-a-ciphertext']));
    }

    expect(fn () => closingEvidence($case === 'unknown closing' ? strtolower((string) Str::ulid()) : $closing->id))
        ->toThrow(DisbursementViolation::class, 'DISBURSEMENT_CLOSING_UNAVAILABLE');
})->with(['unknown closing', 'unreadable closing payload', 'unreadable intent payload']);

it('refuses a stored closing that contradicts itself or its ancestry', function (string $case): void {
    $closing = closingFor($case === 'failure shape with dates' ? 'reconciled_failure' : 'reconciled_success');
    $disbursement = Disbursement::query()->findOrFail($closing->disbursement_id);
    $intent = DisbursementIntent::query()->findOrFail($closing->intent_id);
    $reconciliation = DisbursementReconciliation::query()->findOrFail($closing->reconciliation_id);
    $closings = 'disbursement_closings_protected';
    match ($case) {
        'closing sha only' => withTriggerLifted('disbursement_closings', $closings,
            fn () => DB::table('disbursement_closings')->where('id', $closing->id)->update(['sha256' => str_repeat('0', 64)])),
        'closing column against payload' => withTriggerLifted('disbursement_closings', $closings,
            fn () => DB::table('disbursement_closings')->where('id', $closing->id)->update(['causes' => json_encode(['mandate'])])),
        'closing payload rehashed against column' => rewritten($closing, $closings, [], ['effective_date' => '2028-02-01']),
        'closing payload missing effective_at' => rewritten($closing, $closings, [], ['effective_at' => null]),
        'malformed recorded_at' => rewritten($closing, $closings, [], ['recorded_at' => 'yesterday']),
        'non-string causes' => rewritten($closing, $closings, ['causes' => [1]], ['causes' => [1]]),
        'failure shape with dates' => (function () use ($closing): void {
            withoutConstraint('disbursement_closings', 'disbursement_closing_facts');
            rewritten($closing, 'disbursement_closings_protected', ['due_dates' => ['2027-01-01']], ['due_dates' => ['2027-01-01']]);
        })(),
        'unknown cause' => (function () use ($closing): void {
            withoutConstraint('disbursement_closings', 'disbursement_closing_facts');
            rewritten($closing, 'disbursement_closings_protected', ['cause' => 'reconciled_later'], ['cause' => 'reconciled_later']);
        })(),
        'disbursement sha only' => withTriggerLifted('disbursements', 'disbursements_protected',
            fn () => DB::table('disbursements')->where('id', $disbursement->id)->update(['sha256' => str_repeat('0', 64)])),
        'disbursement column against payload' => withTriggerLifted('disbursements', 'disbursements_protected',
            fn () => DB::table('disbursements')->where('id', $disbursement->id)->update(['commitment_count' => 3])),
        'disbursement term out of schedule range' => (function () use ($disbursement): void {
            withoutConstraint('disbursements', 'disbursement_facts');
            rewritten($disbursement, 'disbursements_protected', ['term_months' => 121], ['term_months' => 121]);
        })(),
        'intent sha only' => withTriggerLifted('disbursement_intents', 'disbursement_intents_protected',
            fn () => DB::table('disbursement_intents')->where('id', $intent->id)->update(['sha256' => str_repeat('0', 64)])),
        'intent commitments binding' => withTriggerLifted('disbursement_intents', 'disbursement_intents_protected',
            fn () => DB::table('disbursement_intents')->where('id', $intent->id)->update(['commitments_digest' => str_repeat('f', 64)])),
        'intent payload of another disbursement' => rewritten($intent, 'disbursement_intents_protected', [], ['disbursement_id' => strtolower((string) Str::ulid())]),
        'reconciliation decision' => withTriggerLifted('disbursement_reconciliations', 'disbursement_reconciliations_protected',
            fn () => DB::table('disbursement_reconciliations')->where('id', $reconciliation->id)->update(['decision' => 'matched_failure'])),
        'reconciliation compared another operation' => withTriggerLifted('disbursement_reconciliations', 'disbursement_reconciliations_protected',
            fn () => DB::table('disbursement_reconciliations')->where('id', $reconciliation->id)->update(['comparison' => json_encode([
                ...$reconciliation->comparison, 'intent' => [...$reconciliation->comparison['intent'], 'operation_id' => strtolower((string) Str::ulid())]])])),
        'reconciliation observed another event' => withTriggerLifted('disbursement_reconciliations', 'disbursement_reconciliations_protected',
            fn () => DB::table('disbursement_reconciliations')->where('id', $reconciliation->id)->update(['comparison' => json_encode([
                ...$reconciliation->comparison, 'observed' => [...$reconciliation->comparison['observed'], 'event_id' => 'evt-other']])])),
        'final observation not applied' => withTriggerLifted('disbursement_provider_events', 'disbursement_provider_events_immutable',
            fn () => DB::table('disbursement_provider_events')->where('id', $reconciliation->provider_event_id)->update(['disposition' => 'stale'])),
        'effective_at against the reconciliation' => rewritten($closing, $closings, ['effective_at' => '2028-01-31T09:00:00+00:00'],
            ['effective_at' => '2028-01-31T09:00:00+00:00']),
        'effective date not recomputed' => rewritten($closing, $closings, ['effective_date' => '2028-02-01'], ['effective_date' => '2028-02-01']),
        'due dates not recomputed' => rewritten($closing, $closings, ['due_dates' => array_reverse($closing->due_dates ?? [])],
            ['due_dates' => array_reverse($closing->due_dates ?? [])]),
        default => throw new LogicException($case),
    };

    expect(fn () => closingEvidence($closing->id))->toThrow(DisbursementViolation::class, 'DISBURSEMENT_CLOSING_INTEGRITY_FAILED');
})->with(['closing sha only', 'closing column against payload', 'closing payload rehashed against column', 'closing payload missing effective_at',
    'malformed recorded_at', 'non-string causes', 'failure shape with dates', 'unknown cause', 'disbursement sha only', 'disbursement column against payload',
    'disbursement term out of schedule range', 'intent sha only', 'intent commitments binding', 'intent payload of another disbursement',
    'reconciliation decision', 'reconciliation compared another operation', 'reconciliation observed another event', 'final observation not applied',
    'effective_at against the reconciliation', 'effective date not recomputed', 'due dates not recomputed']);

it('authenticates the approve operation of an approve-time failed closing', function (string $case, string $reason): void {
    $closing = closingFor('approve_recheck');
    match ($case) {
        'proof released from the operation' => withTriggerLifted('disbursement_step_up_proofs', 'disbursement_step_up_proofs_protected',
            fn () => DB::table('disbursement_step_up_proofs')->where('consumed_operation_id', $closing->operation_id)->update(['consumed_operation_id' => null, 'consumed_at' => null])),
        'proof for another amount' => withTriggerLifted('disbursement_step_up_proofs', 'disbursement_step_up_proofs_protected',
            fn () => DB::table('disbursement_step_up_proofs')->where('consumed_operation_id', $closing->operation_id)->update(['amount' => '5000'])),
        'operation of another command' => withTriggerLifted('command_operations', 'command_operations_immutable',
            fn () => DB::table('command_operations')->where('id', $closing->operation_id)->update(['command' => 'disbursement.reject'])),
        'operation on another target' => withTriggerLifted('command_operations', 'command_operations_immutable',
            fn () => DB::table('command_operations')->where('id', $closing->operation_id)->update(['target_id' => strtolower((string) Str::ulid())])),
        default => throw new LogicException($case),
    };

    expect(fn () => closingEvidence($closing->id))->toThrow(DisbursementViolation::class, $reason);
})->with([
    'proof released from the operation' => ['proof released from the operation', 'DISBURSEMENT_CLOSING_UNAVAILABLE'],
    'proof for another amount' => ['proof for another amount', 'DISBURSEMENT_CLOSING_INTEGRITY_FAILED'],
    'operation of another command' => ['operation of another command', 'DISBURSEMENT_CLOSING_INTEGRITY_FAILED'],
    'operation on another target' => ['operation on another target', 'DISBURSEMENT_CLOSING_INTEGRITY_FAILED'],
]);
