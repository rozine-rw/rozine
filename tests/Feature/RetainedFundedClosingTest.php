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
use App\Domain\Disbursement\DisbursementViolation;
use App\Infrastructure\Disbursement\SyntheticDisbursementSources;
use App\Infrastructure\Primary\RetainedFundedClosing;
use App\Models\DisbursementClosing;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\DisbursementFixture;

/**
 * Real command/intent/provider/reconciliation ancestry with explicitly synthetic funding/current
 * admission. No schema-only PrimaryHoldingFixture closing is financial source authority here.
 *
 * @param  ArrayObject<int, array{campaign: FundedCampaign, instruction: IssueInstruction|FailedClosing, evidence: ClosingEvidence, command_visible: bool}>  $seen
 */
function recordingPrimaryClosingAuthority(ArrayObject $seen): FundedCampaigns
{
    return new class(app(SyntheticDisbursementSources::class), app(RetainedFundedClosing::class), $seen) implements FundedCampaigns
    {
        /** @param ArrayObject<int, array{campaign: FundedCampaign, instruction: IssueInstruction|FailedClosing, evidence: ClosingEvidence, command_visible: bool}> $seen */
        public function __construct(private FundedCampaigns $inner, private RetainedFundedClosing $authority, private ArrayObject $seen) {}

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
            $this->seen[] = ['campaign' => $campaign, 'instruction' => $instruction, 'evidence' => $this->authority->issued($campaign, $instruction), 'command_visible' => false];
            $this->inner->issue($campaign, $instruction);
        }

        public function failClose(FundedCampaign $campaign, FailedClosing $instruction): void
        {
            $evidence = $this->authority->failed($campaign, $instruction);
            $this->seen[] = ['campaign' => $campaign, 'instruction' => $instruction, 'evidence' => $evidence,
                'command_visible' => $evidence->operationId !== null && DB::table('command_operations')->where('id', $evidence->operationId)->exists()];
            $this->inner->failClose($campaign, $instruction);
        }
    };
}

/** @return ArrayObject<int, array{campaign: FundedCampaign, instruction: IssueInstruction|FailedClosing, evidence: ClosingEvidence, command_visible: bool}> */
function primaryClosingCalls(): ArrayObject
{
    return new ArrayObject;
}

/** @return array{campaign: FundedCampaign, instruction: IssueInstruction|FailedClosing, evidence: ClosingEvidence, command_visible: bool} */
function retainedPrimaryClosingCall(string $cause): array
{
    $seen = primaryClosingCalls();
    app()->instance(FundedCampaigns::class, recordingPrimaryClosingAuthority($seen));
    if (in_array($cause, ['approve_recheck', 'worker_recheck'], true)) {
        ['campaign' => $campaign, 'disbursement' => $disbursement] = DisbursementFixture::funded();
        $maker = DisbursementFixture::staff(['treasury']);
        $checker = DisbursementFixture::staff(['approver']);
        DisbursementFixture::command($maker, $disbursement, 'authorize', 0);
        $proof = DisbursementFixture::stepUp($checker, $disbursement)['proof'];
        if ($cause === 'approve_recheck') {
            DisbursementFixture::sources()->scriptRecheck($campaign->campaignId, 'failed', ['restriction']);
            DisbursementFixture::command($checker, $disbursement, 'approve', 1, $proof);
        } else {
            DB::transaction(function () use ($checker, $disbursement, $proof, $campaign): void {
                DisbursementFixture::command($checker, $disbursement, 'approve', 1, $proof);
                DisbursementFixture::sources()->scriptRecheck($campaign->campaignId, 'failed', ['exposure']);
            });
        }
    } else {
        ['intent' => $intent] = DisbursementFixture::approved();
        app(RecordPayoutEvent::class)->handle(DisbursementFixture::provider()->callback($intent->id,
            $cause === 'reconciled_success' ? 'succeeded' : 'failed', ['effective_at' => '2028-01-31T08:00:00+00:00']));
    }
    expect($seen)->toHaveCount(1);

    return $seen->getArrayCopy()[0] ?? throw new LogicException('Closing callback was not observed.');
}

function readPrimaryClosingCall(RetainedFundedClosing $authority, FundedCampaign $campaign, IssueInstruction|FailedClosing $instruction): ClosingEvidence
{
    return $instruction instanceof IssueInstruction ? $authority->issued($campaign, $instruction) : $authority->failed($campaign, $instruction);
}

it('binds every real closing cause immediately and on retained SELECT-only replay', function (string $cause): void {
    $call = retainedPrimaryClosingCall($cause);
    $evidence = $call['evidence'];
    if ($cause === 'approve_recheck') {
        expect($call['command_visible'])->toBeFalse()
            ->and(DB::table('command_operations')->where('id', $evidence->operationId)->exists())->toBeTrue();
    }
    $this->travel(1)->hours();
    $queries = [];
    DB::listen(function (QueryExecuted $event) use (&$queries): void {
        $queries[] = $event->sql;
    });
    $read = DB::transaction(fn (): ClosingEvidence => readPrimaryClosingCall(app(RetainedFundedClosing::class), $call['campaign'], $call['instruction']));
    expect($read)->toEqual($evidence)->and($queries)->not->toBeEmpty();
    foreach ($queries as $query) {
        expect(strtolower($query))->toStartWith('select')->not->toContain('for update', 'for share');
    }
})->with(['approve_recheck', 'worker_recheck', 'reconciled_failure', 'reconciled_success']);

it('refuses each distinct funding binding from an otherwise authenticated closing projection', function (string $cause): void {
    $call = retainedPrimaryClosingCall($cause);
    $evidence = $call['evidence'];
    foreach (['closingId' => strtolower((string) Str::ulid()), 'disbursementId' => strtolower((string) Str::ulid()),
        'campaignId' => strtolower((string) Str::ulid()), 'businessId' => strtolower((string) Str::ulid()),
        'exposureReservationId' => strtolower((string) Str::ulid()), 'amount' => (string) ((int) $evidence->amount + 5000),
        'currency' => 'USD', 'commitmentsDigest' => str_repeat('0', 64), 'commitmentCount' => $evidence->commitmentCount + 1,
        'termMonths' => $evidence->termMonths + 1] as $field => $value) {
        $other = new ClosingEvidence(...array_replace(get_object_vars($evidence), [$field => $value]));
        $source = new class($other) implements DisbursementClosingEvidence
        {
            public function __construct(private ClosingEvidence $evidence) {}

            public function find(string $closingId): ClosingEvidence
            {
                return $this->evidence;
            }
        };
        expect(fn () => readPrimaryClosingCall(new RetainedFundedClosing($source), $call['campaign'], $call['instruction']))
            ->toThrow(RuntimeException::class, 'PRIMARY_CLOSING_MISMATCH');
    }
})->with(['approve_recheck', 'reconciled_success']);

it('refuses every supplied issue field against genuine retained authority', function (): void {
    $call = retainedPrimaryClosingCall('reconciled_success');
    foreach (['closingId' => strtolower((string) Str::ulid()), 'disbursementId' => strtolower((string) Str::ulid()),
        'intentOperationId' => strtolower((string) Str::ulid()), 'effectiveAt' => '2028-01-31T09:00:00+00:00',
        'effectiveDate' => '2028-02-01', 'dueDates' => ['2028-03-01'], 'issuedAt' => now('UTC')->addSecond()->toIso8601String()] as $field => $value) {
        $other = new IssueInstruction(...array_replace(get_object_vars($call['instruction']), [$field => $value]));
        expect(fn () => DB::transaction(fn (): ClosingEvidence => app(RetainedFundedClosing::class)->issued($call['campaign'], $other)))
            ->toThrow($field === 'closingId' ? DisbursementViolation::class : RuntimeException::class,
                $field === 'closingId' ? 'DISBURSEMENT_CLOSING_UNAVAILABLE' : 'PRIMARY_CLOSING_MISMATCH');
    }
});

it('refuses every supplied failure field against genuine retained authority', function (string $cause): void {
    $call = retainedPrimaryClosingCall($cause);
    foreach (['closingId' => strtolower((string) Str::ulid()), 'disbursementId' => strtolower((string) Str::ulid()),
        'cause' => $cause === 'approve_recheck' ? 'worker_recheck' : 'approve_recheck', 'causes' => ['dscr']] as $field => $value) {
        $other = new FailedClosing(...array_replace(get_object_vars($call['instruction']), [$field => $value]));
        expect(fn () => DB::transaction(fn (): ClosingEvidence => app(RetainedFundedClosing::class)->failed($call['campaign'], $other)))
            ->toThrow($field === 'closingId' ? DisbursementViolation::class : RuntimeException::class,
                $field === 'closingId' ? 'DISBURSEMENT_CLOSING_UNAVAILABLE' : 'PRIMARY_CLOSING_MISMATCH');
    }
})->with(['approve_recheck', 'worker_recheck', 'reconciled_failure']);

it('refuses crossing issued and failed closings', function (): void {
    $issued = retainedPrimaryClosingCall('reconciled_success');
    expect(fn () => DB::transaction(fn (): ClosingEvidence => app(RetainedFundedClosing::class)->failed($issued['campaign'],
        new FailedClosing($issued['evidence']->closingId, $issued['evidence']->disbursementId, 'reconciled_failure', ['provider_failure']))))
        ->toThrow(RuntimeException::class, 'PRIMARY_CLOSING_MISMATCH');
    $failed = retainedPrimaryClosingCall('approve_recheck');
    expect(fn () => DB::transaction(fn (): ClosingEvidence => app(RetainedFundedClosing::class)->issued($failed['campaign'],
        new IssueInstruction($failed['evidence']->closingId, $failed['evidence']->disbursementId, 'other', '2028-01-31T08:00:00+00:00',
            '2028-01-31', ['2028-02-29'], $failed['evidence']->recordedAt))))
        ->toThrow(RuntimeException::class, 'PRIMARY_CLOSING_MISMATCH');
});

it('propagates unavailable source evidence instead of creating a financial failure', function (): void {
    $call = retainedPrimaryClosingCall('approve_recheck');
    $before = DisbursementClosing::query()->count();
    $source = new class implements DisbursementClosingEvidence
    {
        public function find(string $closingId): ClosingEvidence
        {
            throw new DisbursementViolation('DISBURSEMENT_CLOSING_UNAVAILABLE');
        }
    };
    expect(fn () => readPrimaryClosingCall(new RetainedFundedClosing($source), $call['campaign'], $call['instruction']))
        ->toThrow(DisbursementViolation::class, 'DISBURSEMENT_CLOSING_UNAVAILABLE');
    expect(DisbursementClosing::query()->count())->toBe($before);
});

it('requires the caller transaction even for retained replay', function (): void {
    $call = retainedPrimaryClosingCall('approve_recheck');
    expect(fn () => readPrimaryClosingCall(app(RetainedFundedClosing::class), $call['campaign'], $call['instruction']))
        ->toThrow(DisbursementViolation::class, 'DISBURSEMENT_CLOSING_TRANSACTION_REQUIRED');
});

it('propagates genuine closing corruption without manufacturing a failed condition', function (): void {
    $call = retainedPrimaryClosingCall('reconciled_success');
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('ALTER TABLE disbursement_closings DISABLE TRIGGER disbursement_closings_protected');
    try {
        DB::table('disbursement_closings')->where('id', $call['evidence']->closingId)->update(['sha256' => str_repeat('0', 64)]);
    } finally {
        DB::statement('ALTER TABLE disbursement_closings ENABLE TRIGGER disbursement_closings_protected');
    }
    expect(fn () => DB::transaction(fn (): ClosingEvidence => readPrimaryClosingCall(app(RetainedFundedClosing::class), $call['campaign'], $call['instruction'])))
        ->toThrow(DisbursementViolation::class, 'DISBURSEMENT_CLOSING_INTEGRITY_FAILED');
    expect(DisbursementClosing::query()->where('kind', 'failed_closing')->exists())->toBeFalse();
});
