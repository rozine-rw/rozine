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
use App\Application\Environment\EnvironmentIsolation;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Domain\Disbursement\DisbursementViolation;
use App\Infrastructure\Disbursement\SyntheticDisbursementSources;
use App\Models\CommandOperation;
use App\Models\Disbursement;
use App\Models\DisbursementClosing;
use App\Models\DisbursementEvent;
use App\Models\DisbursementIntent;
use App\Models\DisbursementReconciliation;
use App\Models\DisbursementStepUpProof;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\DisbursementFixture;
use Tests\Support\PrimaryHoldingFixture;

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

/** A real closing for one cause, through the store's own automatic flows. */
function closingFor(string $cause): DisbursementClosing
{
    if ($cause === 'auto_recheck' || $cause === 'worker_recheck') {
        ['campaign' => $campaign, 'disbursement' => $disbursement] = DisbursementFixture::funded();
        if ($cause === 'auto_recheck') {
            DisbursementFixture::sources()->scriptRecheck($campaign->campaignId, 'failed', ['restriction']);
            DisbursementFixture::release($campaign);
        } else {
            // The recheck before the intent passes; the worker's fresh recheck after commit fails.
            DB::transaction(function () use ($campaign): void {
                DisbursementFixture::release($campaign);
                DisbursementFixture::sources()->scriptRecheck($campaign->campaignId, 'failed', ['exposure']);
            });
        }

        return DisbursementClosing::query()->where('disbursement_id', $disbursement->id)->sole();
    }
    ['intent' => $intent] = DisbursementFixture::paid();
    app(RecordPayoutEvent::class)->handle(DisbursementFixture::provider()->callback($intent->id, $cause === 'reconciled_success' ? 'succeeded' : 'failed',
        ['effective_at' => '2028-01-31T08:00:00+00:00']));

    return DisbursementClosing::query()->where('intent_id', $intent->id)->sole();
}

/**
 * A legacy approve-time failed closing as the retired maker/checker release recorded it, inserted
 * raw with its completed approve command; its deferred authority check is still pending.
 */
function legacyApproveClosing(): DisbursementClosing
{
    ['campaign' => $campaign] = PrimaryHoldingFixture::committed();

    return DisbursementClosing::query()->findOrFail(PrimaryHoldingFixture::issuedClosing($campaign, kind: 'failed_closing'));
}

/**
 * A failed closing the retired maker/checker release recorded at approve, written row by row as
 * that flow wrote it (the disbursement opened before the platform fee, the maker's authorization,
 * the checker's consumed step-up proof, the closing with its approve authority, the failed-closing
 * event and the completed approve command). It commits its deferred checks under the schema before
 * the platform fee and automatic disbursement migrations, which then run over it. No trigger or
 * constraint is lifted.
 */
function legacyApprovedFailedClosing(): DisbursementClosing
{
    $campaign = DisbursementFixture::sources()->fund();
    $migrations = ['2026_10_16_161905_add_platform_fee_to_disbursements', '2026_10_16_180415_disburse_funded_campaigns_automatically'];
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    foreach (array_reverse($migrations) as $migration) {
        (require database_path('migrations/'.$migration.'.php'))->down();
    }
    // As in the retired flow's own transaction, the operation references are checked at commit.
    DB::statement('SET CONSTRAINTS ALL DEFERRED');
    $json = app(CanonicalJson::class);
    $digest = fn (array $payload): string => hash('sha256', $json->encode($payload));
    [$maker, $checker] = [User::factory()->create()->id, User::factory()->create()->id];
    $operation = fn (int $actor, string $command, array $facts = []): CommandOperation => CommandOperation::factory()->create([...['actor_key' => 'staff:'.$actor,
        'actor_user_id' => $actor, 'command' => $command, 'target_type' => 'disbursement'], ...$facts]);

    $opened = ['campaign_id' => $campaign->campaignId, 'business_id' => $campaign->businessId, 'business_name' => $campaign->businessName,
        'title' => $campaign->title, 'exposure_reservation_id' => $campaign->exposureReservationId, 'principal' => $campaign->principal,
        'funded_at' => $campaign->fundedAt, 'term_months' => $campaign->termMonths, 'commitments_digest' => $campaign->commitmentsDigest(),
        'commitment_ids' => array_map(fn ($commitment): string => $commitment->id, $campaign->commitments)];
    $disbursement = new Disbursement;
    $disbursement->forceFill(['business_campaign_id' => $campaign->campaignId, 'business_id' => $campaign->businessId,
        'exposure_reservation_id' => $campaign->exposureReservationId, 'amount' => $campaign->principal, 'currency' => 'RWF',
        'commitments_digest' => $campaign->commitmentsDigest(), 'commitment_count' => count($campaign->commitments), 'term_months' => $campaign->termMonths,
        'funded_at' => $campaign->fundedAt, 'environment' => app(EnvironmentIsolation::class)->profile(), 'payload' => $opened, 'sha256' => $digest($opened),
        'created_at' => now('UTC')])->save();

    $event = function (int $revision, string $kind, int $actor, string $operationId, string $requestId, array $facts, ?string $destination = null) use ($disbursement, $digest): void {
        $record = new DisbursementEvent;
        $record->id = strtolower((string) Str::ulid());
        $payload = [...$facts, 'event_id' => $record->id, 'disbursement_id' => $disbursement->id, 'revision' => $revision, 'kind' => $kind,
            'actor_user_id' => $actor, 'operation_id' => $operationId, 'request_id' => $requestId, 'recorded_at' => now('UTC')->toIso8601String()];
        $binding = $destination === null ? null : $digest(['campaign_id' => $disbursement->business_campaign_id,
            'exposure_reservation_id' => $disbursement->exposure_reservation_id, 'amount' => $disbursement->amount, 'currency' => 'RWF',
            'destination_sha256' => $destination, 'commitments_digest' => $disbursement->commitments_digest]);
        $record->forceFill(['disbursement_id' => $disbursement->id, 'revision' => $revision, 'kind' => $kind, 'actor_user_id' => $actor,
            'operation_id' => $operationId, 'request_id' => $requestId, 'binding_sha256' => $binding, 'destination_sha256' => $destination,
            'payload' => $payload, 'sha256' => $digest($payload), 'created_at' => now('UTC')])->save();
    };
    $authorize = $operation($maker, 'disbursement.authorize', ['target_id' => $disbursement->id]);
    $destination = str_repeat('d', 64);
    $event(1, 'authorized', $maker, $authorize->id, $authorize->request_id, ['reason' => 'Checked against the funded campaign.',
        'precheck' => ['state' => 'passed', 'checked_at' => now('UTC')->toIso8601String(), 'policy_version' => 'synthetic', 'causes' => []],
        'destination' => ['id' => 'dest-1', 'revision' => 1, 'masked' => '****0001']], $destination);

    $closingId = strtolower((string) Str::ulid());
    $requestId = (string) Str::uuid();
    $approveId = strtolower((string) Str::ulid());
    $proof = new DisbursementStepUpProof;
    $proof->forceFill(['purpose' => 'disbursement.approve', 'actor_user_id' => $checker, 'disbursement_id' => $disbursement->id,
        'revision' => 1, 'amount' => $disbursement->amount, 'destination_sha256' => $destination, 'intent_digest' => str_repeat('i', 64),
        'credential_binding' => str_repeat('c', 64), 'proof_sha256' => hash('sha256', Str::random(64)), 'expires_at' => now('UTC')->addMinutes(5)->startOfSecond(),
        'created_at' => now('UTC')])->save();
    $proof->forceFill(['consumed_at' => now('UTC'), 'consumed_operation_id' => $approveId])->save();
    $recorded = now('UTC')->startOfSecond()->toImmutable();
    $closed = ['closing_id' => $closingId, 'disbursement_id' => $disbursement->id, 'kind' => 'failed_closing', 'cause' => 'approve_recheck',
        'causes' => ['restriction'], 'intent_id' => null, 'reconciliation_id' => null, 'provider_event_id' => null, 'operation_id' => $approveId,
        'command' => 'disbursement.approve', 'actor_user_id' => $checker, 'request_id' => $requestId, 'effective_at' => null, 'effective_date' => null,
        'due_dates' => null, 'refund' => ['commitments' => $disbursement->commitment_count, 'amount' => $disbursement->amount, 'fee' => '0'],
        'recorded_at' => $recorded->toIso8601String()];
    $closing = new DisbursementClosing;
    $closing->id = $closingId;
    $closing->forceFill(['disbursement_id' => $disbursement->id, 'intent_id' => null, 'reconciliation_id' => null, 'kind' => 'failed_closing',
        'cause' => 'approve_recheck', 'causes' => ['restriction'], 'operation_id' => $approveId, 'actor_user_id' => $checker, 'request_id' => $requestId,
        'effective_at' => null, 'effective_date' => null, 'due_dates' => null, 'payload' => $closed, 'sha256' => $digest($closed), 'created_at' => $recorded])->save();
    $event(2, 'failed_closing', $checker, $approveId, $requestId, ['reason' => 'Checked against the funded campaign.', 'closing_id' => $closingId,
        'causes' => ['restriction'], 'policy_version' => 'synthetic']);
    // The journal records the approve only after its effect returns, naming the closing in its receipt.
    $operation($checker, 'disbursement.approve', ['id' => $approveId, 'request_id' => $requestId, 'target_id' => $disbursement->id,
        'result' => ['status' => 'completed', 'code' => 'CAMPAIGN_FAILED_CLOSING', 'data' => ['receipt' => ['receipt_id' => $closingId]]]]);
    PrimaryHoldingFixture::flushDeferredChecks();

    foreach ($migrations as $migration) {
        (require database_path('migrations/'.$migration.'.php'))->up();
    }

    return DisbursementClosing::query()->findOrFail($closingId);
}

/**
 * Runs a change with one table's append-only trigger lifted; the test's transaction rolls it back.
 * Deferred keys are checked first, since PostgreSQL refuses DDL on a table with pending trigger events.
 */
function withTriggerLifted(string $table, string $trigger, Closure $change, bool $flushDeferred = true): void
{
    if ($flushDeferred) {
        DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    }
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

/**
 * Rewrites one part of a reconciliation's retained comparison; null removes it.
 *
 * @param  array<string, string>|null  $changes
 */
function compared(DisbursementReconciliation $reconciliation, string $part, ?array $changes): void
{
    $comparison = $reconciliation->comparison;
    if ($changes === null) {
        unset($comparison[$part]);
    } else {
        $comparison[$part] = [...$comparison[$part], ...$changes];
    }
    withTriggerLifted('disbursement_reconciliations', 'disbursement_reconciliations_protected',
        fn () => DB::table('disbursement_reconciliations')->where('id', $reconciliation->id)->update(['comparison' => json_encode($comparison)]));
}

/**
 * Changes the selected observation and what the reconciliation retained of it alike, so only its
 * binding to the intent can refuse it.
 *
 * @param  array<string, string>  $columns
 * @param  array<string, string>  $observed
 */
function observedAs(DisbursementReconciliation $reconciliation, array $columns, array $observed): void
{
    withTriggerLifted('disbursement_provider_events', 'disbursement_provider_events_immutable',
        fn () => DB::table('disbursement_provider_events')->where('id', $reconciliation->provider_event_id)->update($columns));
    compared($reconciliation, 'observed', $observed);
}

/** Makes a legacy closing's recorded approve command disagree with its retained authority in one respect. */
function mismatchedOperation(string $operationId, string $case, bool $flushDeferred): void
{
    $change = match ($case) {
        'operation of another command' => ['command' => 'disbursement.reject'],
        'operation on another target' => ['target_id' => strtolower((string) Str::ulid())],
        'operation by another actor' => ['actor_user_id' => User::factory()->create()->id],
        'operation of another request' => ['request_id' => (string) Str::uuid()],
        'operation with another outcome' => ['result' => DB::raw("jsonb_set(result, '{code}', '\"DISBURSEMENT_INTENT_RECORDED\"')")],
        'operation receipt of another closing' => ['result' => DB::raw("jsonb_set(result, '{data,receipt,receipt_id}', '\"01kanotherclosing000000000\"')")],
        default => throw new LogicException($case),
    };
    withTriggerLifted('command_operations', 'command_operations_immutable',
        fn () => DB::table('command_operations')->where('id', $operationId)->update($change), $flushDeferred);
}

it('authenticates every cause inside the closing call and afterwards, with the retained issue instant', function (string $cause): void {
    $seen = closingCalls();
    app()->instance(FundedCampaigns::class, evidenceRecordingFunding($seen));
    // Time moves on between recording the closing and issuing, so only the one retained instant matches.
    DisbursementClosing::saved(fn () => $this->travel(1)->hours());
    $closing = closingFor($cause);
    $disbursement = Disbursement::query()->findOrFail($closing->disbursement_id);
    $intent = DisbursementIntent::query()->where('disbursement_id', $disbursement->id)->first();
    $evidence = closingEvidence($closing->id);
    ['instruction' => $instruction, 'evidence' => $inside] = $seen[0];
    $issued = $cause === 'reconciled_success';

    expect($seen)->toHaveCount(1)->and($inside)->toEqual($evidence)
        ->and([$evidence->closingId, $evidence->kind, $evidence->cause, $evidence->causes])->toBe([$closing->id, $issued ? 'issued' : 'failed_closing', $cause,
            ['auto_recheck' => ['restriction'], 'worker_recheck' => ['exposure']][$cause] ?? []])
        ->and([$evidence->disbursementId, $evidence->campaignId, $evidence->businessId, $evidence->exposureReservationId, $evidence->amount, $evidence->currency,
            $evidence->commitmentsDigest, $evidence->commitmentCount, $evidence->termMonths, $evidence->disbursementSha256])
        ->toBe([$disbursement->id, $disbursement->business_campaign_id, $disbursement->business_id, $disbursement->exposure_reservation_id, $disbursement->amount,
            'RWF', $disbursement->commitments_digest, 2, 12, $disbursement->sha256])
        ->and([$evidence->intentId, $evidence->intentOperationId, $evidence->reconciliationId, $evidence->operationId])
        ->toBe([$intent?->id, $intent?->operation_id, $closing->reconciliation_id, $closing->operation_id])
        ->and([$evidence->recordedAt, $evidence->closingSha256])->toBe([$closing->payload['recorded_at'], $closing->sha256])
        ->and($closing->created_at->utc()->toIso8601String())->toBe($evidence->recordedAt)
        ->and($instruction->closingId)->toBe($closing->id)
        ->and($closing->payload['provider_event_id'])->toBe($closing->reconciliation_id === null ? null
            : DisbursementReconciliation::query()->findOrFail($closing->reconciliation_id)->provider_event_id);
    // No closing carries staff authority: the system recorded the intent, or closed before any existed.
    expect([$closing->operation_id, $closing->payload['operation_id'], $closing->payload['command'], $closing->payload['actor_user_id'],
        $closing->payload['request_id'], $closing->actor_user_id, $closing->request_id])->toBe([null, null, null, null, null, null, null])
        ->and($intent === null)->toBe($cause === 'auto_recheck');
    if ($intent !== null) {
        expect([$intent->maker_user_id, $intent->checker_user_id, $intent->rule_version])->toBe([null, null, 'brs-2.0-svc-r01'])
            ->and(DB::table('command_operations')->where('id', $evidence->intentOperationId)->exists())->toBeFalse();
    }
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
})->with(['auto_recheck', 'worker_recheck', 'reconciled_failure', 'reconciled_success']);

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
})->with(['auto_recheck', 'reconciled_success']);

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
        'created_at against recorded_at' => withTriggerLifted('disbursement_closings', $closings,
            fn () => DB::table('disbursement_closings')->where('id', $closing->id)->update(['created_at' => DB::raw("created_at + interval '1 second'")])),
        'closing payload names another observation' => rewritten($closing, $closings, [], ['provider_event_id' => strtolower((string) Str::ulid())]),
        'reconciliation compared another destination' => compared($reconciliation, 'intent', ['destination_sha256' => str_repeat('f', 64)]),
        'reconciliation compared another provider' => compared($reconciliation, 'intent', ['provider' => 'other']),
        'reconciliation without its observation count' => compared($reconciliation, 'observations', null),
        'reconciliation observed another effective instant' => compared($reconciliation, 'observed', ['effective_at' => '2028-01-31T09:00:00+00:00']),
        'matched reconciliation with causes' => (function () use ($reconciliation): void {
            withoutConstraint('disbursement_reconciliations', 'disbursement_reconciliation_facts');
            withTriggerLifted('disbursement_reconciliations', 'disbursement_reconciliations_protected',
                fn () => DB::table('disbursement_reconciliations')->where('id', $reconciliation->id)->update(['causes' => json_encode(['conflict'])]));
        })(),
        'observation of another amount' => observedAs($reconciliation, ['amount' => '5000'], ['amount' => '5000']),
        'observation of another destination' => observedAs($reconciliation, ['destination_sha256' => str_repeat('e', 64)], ['destination_sha256' => str_repeat('e', 64)]),
        'observation of another operation' => (function () use ($reconciliation): void {
            $other = strtolower((string) Str::ulid());
            observedAs($reconciliation, ['observed_operation_id' => $other], ['operation_id' => $other]);
        })(),
        'observation of another environment' => observedAs($reconciliation, ['environment' => 'other'], ['environment' => 'other']),
        'observation of another reference' => observedAs($reconciliation, ['provider_reference_sha256' => str_repeat('a', 64)],
            ['provider_reference_sha256' => str_repeat('a', 64)]),
        'non-final observation' => observedAs($reconciliation, ['state' => 'pending'], ['state' => 'pending']),
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
    'effective_at against the reconciliation', 'effective date not recomputed', 'due dates not recomputed', 'created_at against recorded_at',
    'closing payload names another observation', 'reconciliation compared another destination', 'reconciliation compared another provider',
    'reconciliation without its observation count', 'reconciliation observed another effective instant', 'matched reconciliation with causes',
    'observation of another amount', 'observation of another destination', 'observation of another operation', 'observation of another environment',
    'observation of another reference', 'non-final observation']);

it('refuses an intent whose destination falls outside its recomputed intent digest', function (): void {
    $closing = closingFor('worker_recheck');
    withTriggerLifted('disbursement_intents', 'disbursement_intents_protected',
        fn () => DB::table('disbursement_intents')->where('id', $closing->intent_id)->update(['destination_sha256' => str_repeat('d', 64)]));

    expect(fn () => closingEvidence($closing->id))->toThrow(DisbursementViolation::class, 'DISBURSEMENT_CLOSING_INTEGRITY_FAILED');
});

it('refuses an automatic closing that claims staff authority', function (): void {
    $closing = closingFor('auto_recheck');
    $actor = User::factory()->create()->id;
    $request = (string) Str::uuid();
    withoutConstraint('disbursement_closings', 'disbursement_closing_authority');
    rewritten($closing, 'disbursement_closings_protected', ['actor_user_id' => $actor, 'request_id' => $request],
        ['actor_user_id' => $actor, 'request_id' => $request]);

    expect(fn () => closingEvidence($closing->id))->toThrow(DisbursementViolation::class, 'DISBURSEMENT_CLOSING_INTEGRITY_FAILED');
});

it('keeps a genuine legacy approve-time failed closing verifiable across the fee and automatic payment migrations', function (): void {
    $closing = legacyApprovedFailedClosing();
    $evidence = closingEvidence($closing->id);

    expect($evidence->cause)->toBe('approve_recheck')
        ->and($evidence->kind)->toBe('failed_closing')
        ->and($evidence->causes)->toBe(['restriction'])
        ->and($evidence->operationId)->toBe($closing->operation_id)
        ->and($evidence->intentId)->toBeNull()
        ->and(Disbursement::query()->findOrFail($closing->disbursement_id)->fee_version)->toBeNull()
        ->and(closingEvidence($closing->id))->toEqual($evidence);
});

it('authenticates the approve command authority of a legacy approve-time failed closing', function (string $case, string $reason): void {
    $closing = legacyApprovedFailedClosing();
    match ($case) {
        'proof released from the operation' => withTriggerLifted('disbursement_step_up_proofs', 'disbursement_step_up_proofs_protected',
            fn () => DB::table('disbursement_step_up_proofs')->where('consumed_operation_id', $closing->operation_id)->update(['consumed_operation_id' => null, 'consumed_at' => null])),
        'proof for another amount' => withTriggerLifted('disbursement_step_up_proofs', 'disbursement_step_up_proofs_protected',
            fn () => DB::table('disbursement_step_up_proofs')->where('consumed_operation_id', $closing->operation_id)->update(['amount' => '5000'])),
        'closing authority of another actor' => rewritten($closing, 'disbursement_closings_protected', ['actor_user_id' => $other = User::factory()->create()->id],
            ['actor_user_id' => $other]),
        default => mismatchedOperation((string) $closing->operation_id, $case, true),
    };

    expect(fn () => closingEvidence($closing->id))->toThrow(DisbursementViolation::class, $reason);
})->with([
    'proof released from the operation' => ['proof released from the operation', 'DISBURSEMENT_CLOSING_UNAVAILABLE'],
    'proof for another amount' => ['proof for another amount', 'DISBURSEMENT_CLOSING_INTEGRITY_FAILED'],
    'closing authority of another actor' => ['closing authority of another actor', 'DISBURSEMENT_CLOSING_INTEGRITY_FAILED'],
    'operation of another command' => ['operation of another command', 'DISBURSEMENT_CLOSING_INTEGRITY_FAILED'],
    'operation on another target' => ['operation on another target', 'DISBURSEMENT_CLOSING_INTEGRITY_FAILED'],
    'operation by another actor' => ['operation by another actor', 'DISBURSEMENT_CLOSING_INTEGRITY_FAILED'],
    'operation of another request' => ['operation of another request', 'DISBURSEMENT_CLOSING_INTEGRITY_FAILED'],
    'operation with another outcome' => ['operation with another outcome', 'DISBURSEMENT_CLOSING_INTEGRITY_FAILED'],
    'operation receipt of another closing' => ['operation receipt of another closing', 'DISBURSEMENT_CLOSING_INTEGRITY_FAILED'],
]);

it('refuses an automatic closing rewritten as a legacy approve-time closing on a disbursement opened under the fee', function (): void {
    $closing = closingFor('auto_recheck');
    $checker = User::factory()->create()->id;
    $approve = CommandOperation::factory()->create(['actor_key' => 'staff:'.$checker, 'actor_user_id' => $checker, 'command' => 'disbursement.approve',
        'target_type' => 'disbursement', 'target_id' => $closing->disbursement_id,
        'result' => ['status' => 'completed', 'code' => 'CAMPAIGN_FAILED_CLOSING', 'data' => ['receipt' => ['receipt_id' => $closing->id]]]]);
    $authority = ['cause' => 'approve_recheck', 'operation_id' => $approve->id, 'actor_user_id' => $checker, 'request_id' => $approve->request_id];
    rewritten($closing, 'disbursement_closings_protected', $authority, [...$authority, 'command' => 'disbursement.approve']);

    expect(fn () => closingEvidence($closing->id))->toThrow(DisbursementViolation::class, 'DISBURSEMENT_CLOSING_INTEGRITY_FAILED');
});

it('lets a legacy approve-time closing commit only with its own recorded approve command', function (string $case): void {
    $closing = legacyApproveClosing();
    if ($case !== 'its own command') {
        // Changed before the deferred check runs, as if the journal had recorded another command.
        mismatchedOperation((string) $closing->operation_id, $case, false);
    }
    $commit = fn () => DB::statement('SET CONSTRAINTS disbursement_closings_authority IMMEDIATE');

    $case === 'its own command' ? expect($commit())->toBeTrue()
        : expect($commit)->toThrow(QueryException::class, 'commits only with its own recorded approve command');
})->with(['its own command', 'operation of another command', 'operation on another target', 'operation by another actor', 'operation of another request',
    'operation with another outcome', 'operation receipt of another closing']);

it('refuses a legacy approve-time closing without its command authority', function (): void {
    $closing = legacyApproveClosing();

    expect(fn () => withTriggerLifted('disbursement_closings', 'disbursement_closings_protected',
        fn () => DB::transaction(fn () => DB::table('disbursement_closings')->where('id', $closing->id)->update(['request_id' => null]))))
        ->toThrow(QueryException::class, 'disbursement_closing_authority');
});
