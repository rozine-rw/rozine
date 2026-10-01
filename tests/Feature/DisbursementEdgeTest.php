<?php

declare(strict_types=1);

use App\Application\Disbursement\Contracts\DisbursementStore;
use App\Application\Disbursement\Contracts\FundedCampaigns;
use App\Application\Disbursement\Contracts\PayoutProvider;
use App\Application\Disbursement\FailedClosing;
use App\Application\Disbursement\FundedCampaign;
use App\Application\Disbursement\FundedCommitment;
use App\Application\Disbursement\IssueInstruction;
use App\Application\Disbursement\ManageDisbursements;
use App\Application\Disbursement\PayoutInstruction;
use App\Application\Disbursement\RecheckResult;
use App\Application\Disbursement\ReconcileDisbursements;
use App\Application\Disbursement\RecordPayoutEvent;
use App\Application\Disbursement\VerifiedPayoutEvent;
use App\Domain\Disbursement\DisbursementViolation;
use App\Domain\Operations\CommandRejection;
use App\Infrastructure\Disbursement\SyntheticDisbursementSources;
use App\Infrastructure\Disbursement\UnavailableFundedCampaigns;
use App\Models\DisbursementClosing;
use App\Models\DisbursementDispatch;
use App\Models\DisbursementIntent;
use App\Models\DisbursementReconciliation;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\DisbursementFixture;

/*
 * Fail-closed edges of the disbursement ports: races between the unlocked read and the locks,
 * changed funding facts, contention, provider failures and the funding port's own invariants.
 */

/**
 * A funding source that delegates to the synthetic one, with hooks for the calls a test needs to
 * disturb.
 */
function disturbedFunding(?Closure $onLockBusiness = null, ?Closure $mapCampaign = null): FundedCampaigns
{
    $inner = app(SyntheticDisbursementSources::class);

    return new class($inner, $onLockBusiness, $mapCampaign) implements FundedCampaigns
    {
        public function __construct(private FundedCampaigns $inner, private ?Closure $onLockBusiness, private ?Closure $mapCampaign) {}

        public function funded(?string $before, int $limit): array
        {
            return $this->inner->funded($before, $limit);
        }

        public function lockBusiness(string $businessId): void
        {
            $this->inner->lockBusiness($businessId);
            if ($this->onLockBusiness !== null) {
                ($this->onLockBusiness)($businessId);
            }
        }

        public function lockFunded(string $campaignId): FundedCampaign
        {
            $campaign = $this->inner->lockFunded($campaignId);

            return $this->mapCampaign === null ? $campaign : ($this->mapCampaign)($campaign);
        }

        public function recheck(FundedCampaign $campaign): RecheckResult
        {
            return $this->inner->recheck($campaign);
        }

        public function issue(FundedCampaign $campaign, IssueInstruction $instruction): void
        {
            $this->inner->issue($campaign, $instruction);
        }

        public function failClose(FundedCampaign $campaign, FailedClosing $closing): void
        {
            $this->inner->failClose($campaign, $closing);
        }
    };
}

function longerTerm(FundedCampaign $campaign): FundedCampaign
{
    return new FundedCampaign($campaign->campaignId, $campaign->businessId, $campaign->businessName, $campaign->title, $campaign->exposureReservationId,
        $campaign->principal, $campaign->fundedAt, $campaign->termMonths + 1, $campaign->commitments);
}

it('refuses a command when the maker changes between the unlocked read and the locks', function (string $path): void {
    ['disbursement' => $disbursement] = DisbursementFixture::funded();
    $maker = DisbursementFixture::staff(['treasury']);
    $checker = DisbursementFixture::staff(['approver']);
    $other = DisbursementFixture::staff(['treasury']);
    DisbursementFixture::command($maker, $disbursement, 'authorize', 0);
    $digest = DisbursementFixture::detail($checker, $disbursement)['approval_binding']['intent_digest'];
    $proof = $path === 'approve' ? DisbursementFixture::stepUp($checker, $disbursement)['proof'] : null;
    $this->travel(11)->minutes();
    $fired = false;
    app()->instance(FundedCampaigns::class, disturbedFunding(function () use (&$fired, $disbursement, $other): void {
        if (! $fired) {
            $fired = true;
            DB::table('disbursement_events')->insert(['id' => strtolower((string) Str::ulid()), 'disbursement_id' => $disbursement->id, 'revision' => 2,
                'kind' => 'authorized', 'actor_user_id' => $other->id, 'operation_id' => strtolower((string) Str::ulid()), 'request_id' => (string) Str::uuid(),
                'binding_sha256' => str_repeat('b', 64), 'destination_sha256' => str_repeat('d', 64), 'payload' => encrypt(json_encode(['reason' => 'x']), false),
                'sha256' => str_repeat('0', 64), 'created_at' => now()]);
        }
    }));
    if ($path === 'step-up') {
        expect(fn () => app(ManageDisbursements::class)->stepUp($checker->id, $disbursement->id, 1, $digest, DisbursementFixture::code($checker)))
            ->toThrow(CommandRejection::class, 'VERSION_CONFLICT');

        return;
    }
    expect(DisbursementFixture::command($checker, $disbursement, 'approve', 2, $proof)['code'])->toBe('VERSION_CONFLICT');
})->with(['step-up', 'approve']);

it('refuses as stale when the funding facts no longer match the opened disbursement, and never issues over them', function (): void {
    ['disbursement' => $disbursement] = DisbursementFixture::funded();
    $maker = DisbursementFixture::staff(['treasury']);
    app()->instance(FundedCampaigns::class, disturbedFunding(null, longerTerm(...)));
    expect(DisbursementFixture::command($maker, $disbursement, 'authorize', 0)['code'])->toBe('DIGEST_STALE');

    app()->forgetInstance(FundedCampaigns::class);
    ['intent' => $intent] = DisbursementFixture::approved();
    app()->instance(FundedCampaigns::class, disturbedFunding(null, longerTerm(...)));
    expect(app(RecordPayoutEvent::class)->handle(DisbursementFixture::provider()->callback($intent->id, 'succeeded')))
        ->toBe(['disposition' => 'applied', 'decision' => 'exception'])
        ->and(DisbursementReconciliation::query()->where('decision', 'exception')->sole()->causes)->toBe(['funding_changed'])
        ->and(DisbursementClosing::query()->count())->toBe(0);
});

it('maps exhausted serialization retries to retryable contention and rethrows other database errors', function (string $code, string $expected): void {
    ['disbursement' => $disbursement] = DisbursementFixture::funded();
    $maker = DisbursementFixture::staff(['treasury']);
    app()->instance(FundedCampaigns::class, disturbedFunding(function () use ($code): void {
        $previous = new class('synthetic '.$code) extends PDOException {};
        (fn () => $this->code = $code)->call($previous);
        throw new QueryException('pgsql', 'select 1', [], $previous);
    }));
    expect(fn () => DisbursementFixture::command($maker, $disbursement, 'authorize', 0))->toThrow($expected === 'contention' ? CommandRejection::class : QueryException::class,
        $expected === 'contention' ? 'RETRYABLE_CONTENTION' : 'synthetic');
})->with(['deadlock' => ['40P01', 'contention'], 'serialization' => ['40001', 'contention'], 'other' => ['42P01', 'other']]);

it('keeps an approved intent queued when the funding source or the dispatcher is unavailable after commit', function (string $case): void {
    ['disbursement' => $disbursement] = DisbursementFixture::funded();
    $maker = DisbursementFixture::staff(['treasury']);
    $checker = DisbursementFixture::staff(['approver']);
    DisbursementFixture::command($maker, $disbursement, 'authorize', 0);
    $proof = DisbursementFixture::stepUp($checker, $disbursement)['proof'];
    DB::transaction(function () use ($checker, $disbursement, $proof, $case): void {
        expect(DisbursementFixture::command($checker, $disbursement, 'approve', 1, $proof)['code'])->toBe('DISBURSEMENT_INTENT_RECORDED');
        $case === 'funding' ? app()->instance(FundedCampaigns::class, new UnavailableFundedCampaigns) : config(['isolation.live_money_enabled' => true]);
    });
    expect(DisbursementDispatch::query()->pluck('phase')->all())->toBe(['queued']);
})->with(['funding', 'dispatcher']);

it('treats a provider that throws on query as no answer, and records a repeated dispatch outcome once', function (): void {
    ['disbursement' => $disbursement, 'intent' => $intent] = DisbursementFixture::approved();
    $treasury = DisbursementFixture::staff(['treasury']);
    app()->instance(PayoutProvider::class, new class implements PayoutProvider
    {
        public function name(): string
        {
            return 'synthetic';
        }

        public function idempotentSends(): bool
        {
            return false;
        }

        public function send(PayoutInstruction $instruction): bool
        {
            return true;
        }

        public function query(PayoutInstruction $instruction): ?VerifiedPayoutEvent
        {
            throw new RuntimeException('Provider timed out.');
        }

        public function verify(array $message): VerifiedPayoutEvent
        {
            throw new LogicException('Not used.');
        }
    });
    expect(DisbursementFixture::command($treasury, $disbursement, 'requery', 3)['data']['observation'])->toBeNull()
        ->and(app(ReconcileDisbursements::class)->handle())->toBe(['queried' => 1, 'observed' => 0, 'decisions' => ['open' => 1]]);
    app(DisbursementStore::class)->recordDispatch($intent->id, false);
    expect(DisbursementDispatch::query()->where('intent_id', $intent->id)->orderBy('id')->pluck('phase')->all())->toBe(['queued', 'claimed', 'sent']);
});

it('refuses a requery key reused for another disbursement and an invalid key', function (): void {
    ['disbursement' => $first] = DisbursementFixture::approved();
    ['disbursement' => $second] = DisbursementFixture::approved();
    $treasury = DisbursementFixture::staff(['treasury']);
    $key = (string) Str::uuid();
    DisbursementFixture::command($treasury, $first, 'requery', 3, null, $key);
    expect(fn () => DisbursementFixture::command($treasury, $second, 'requery', 3, null, $key))->toThrow(CommandRejection::class, 'IDEMPOTENCY_CONFLICT')
        ->and(fn () => DisbursementFixture::command($treasury, $second, 'requery', 3, null, 'not-a-uuid'))->toThrow(CommandRejection::class, 'OPERATION_INPUT_INVALID');
});

it('records an unencodable reason as a validation refusal without storing it', function (): void {
    ['disbursement' => $disbursement] = DisbursementFixture::funded();
    $maker = DisbursementFixture::staff(['treasury']);
    $refused = DisbursementFixture::command($maker, $disbursement, 'authorize', 0, null, null, "bad \xC3\x28 bytes");
    expect($refused['code'])->toBe('VALIDATION_FAILED');
});

it('reports an unavailable funding source from the scheduled reconciler', function (): void {
    app()->instance(FundedCampaigns::class, new UnavailableFundedCampaigns);
    expect(Artisan::call('disbursements:reconcile'))->toBe(0)->and(Artisan::output())->toContain('Funding source: FUNDING_SOURCE_UNAVAILABLE');
});

it('keeps the funding port invariants and its synthetic source exclusive and transactional', function (): void {
    $commitment = fn (string $id, int $units = 1, int $first = 1, int $last = 1, ?string $principal = null): FundedCommitment => new FundedCommitment(
        $id, 'p', 'o', $units, [['first' => $first, 'last' => $last]], [], [], $principal ?? (string) ($units * 5000));
    expect(fn () => $commitment('a', 1, 0, 0))->toThrow(DisbursementViolation::class, 'FUNDED_COMMITMENT_INVALID')
        ->and(fn () => $commitment('a', 2))->toThrow(DisbursementViolation::class, 'FUNDED_COMMITMENT_INVALID')
        ->and(fn () => $commitment('a', 1, 1, 1, '4999'))->toThrow(DisbursementViolation::class, 'FUNDED_COMMITMENT_INVALID')
        ->and(fn () => new FundedCampaign('c', 'b', 'B', 'T', 'e', '10000', 'now', 12, [$commitment('b'), $commitment('a')]))
        ->toThrow(DisbursementViolation::class, 'FUNDED_CAMPAIGN_INVALID')
        ->and(fn () => new FundedCampaign('c', 'b', 'B', 'T', 'e', '15000', 'now', 12, [$commitment('a'), $commitment('b')]))
        ->toThrow(DisbursementViolation::class, 'FUNDED_CAMPAIGN_INVALID')
        ->and(fn () => RecheckResult::failed(['weather'], 'v1'))->toThrow(DisbursementViolation::class, 'RECHECK_CAUSE_INVALID')
        ->and(fn () => RecheckResult::unavailable([], 'v1'))->toThrow(DisbursementViolation::class, 'RECHECK_CAUSE_INVALID');

    $sources = app(SyntheticDisbursementSources::class);
    $campaign = $sources->fund([1]);
    $issue = new IssueInstruction(strtolower((string) Str::ulid()), 'd', 'o', '2027-01-31T08:00:00+00:00', '2027-01-31', ['2027-02-28'], '2027-01-31T09:00:00+00:00');
    expect(fn () => DB::transaction(fn () => $sources->lockFunded(strtolower((string) Str::ulid()))))->toThrow(CommandRejection::class, 'FUNDING_SOURCE_UNAVAILABLE');
    DB::transaction(fn () => $sources->issue($campaign, $issue));
    DB::transaction(fn () => $sources->issue($campaign, $issue));
    expect($sources->effects($campaign->campaignId))->toHaveCount(1)
        ->and(fn () => DB::transaction(fn () => $sources->failClose($campaign, new FailedClosing($issue->closingId, 'd', 'reconciled_failure', []))))
        ->toThrow(DisbursementViolation::class, 'FUNDING_CLOSING_EXCLUSIVE')
        ->and($sources->funded(strtolower((string) Str::ulid()), 5))->toHaveCount(1)
        ->and($sources->funded('0', 5))->toBe([]);
    $unavailable = new UnavailableFundedCampaigns;
    expect(fn () => $unavailable->recheck($campaign))->toThrow(CommandRejection::class, 'FUNDING_SOURCE_UNAVAILABLE')
        ->and(fn () => $unavailable->issue($campaign, $issue))->toThrow(CommandRejection::class, 'FUNDING_SOURCE_UNAVAILABLE')
        ->and(fn () => $unavailable->failClose($campaign, new FailedClosing('x', 'd', 'reconciled_failure', [])))->toThrow(CommandRejection::class, 'FUNDING_SOURCE_UNAVAILABLE');
});

it('never offers a provider call inside a transaction from the synthetic provider', function (): void {
    ['intent' => $intent] = DisbursementFixture::approved();
    $instruction = new PayoutInstruction($intent->id, $intent->operation_id, $intent->provider_reference, $intent->amount, 'RWF', $intent->destination_id,
        $intent->destination_sha256, $intent->environment);
    expect(fn () => DB::transaction(fn () => app(PayoutProvider::class)->send($instruction)))->toThrow(LogicException::class, 'DISBURSEMENT_PROVIDER_TRANSACTION_OPEN')
        ->and(DisbursementIntent::query()->count())->toBe(1);
});

it('refuses a synthetic funding lock outside the caller\'s transaction', function (): void {
    $sources = app(SyntheticDisbursementSources::class);
    $campaign = $sources->fund([1]);
    expect(fn () => $sources->lockBusiness($campaign->businessId))->toThrow(DisbursementViolation::class, 'FUNDING_TRANSACTION_REQUIRED');
    DB::transaction(fn () => $sources->lockBusiness($campaign->businessId));
});
