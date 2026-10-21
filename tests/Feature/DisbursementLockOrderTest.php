<?php

declare(strict_types=1);

use App\Application\Disbursement\Contracts\FundedCampaigns;
use App\Application\Disbursement\Contracts\PayoutDestinations;
use App\Application\Disbursement\FailedClosing;
use App\Application\Disbursement\FundedCampaign;
use App\Application\Disbursement\IssueInstruction;
use App\Application\Disbursement\ManageDisbursements;
use App\Application\Disbursement\OpenFundedDisbursements;
use App\Application\Disbursement\RecheckResult;
use App\Application\Disbursement\ReconcileDisbursements;
use App\Application\Disbursement\RecordPayoutEvent;
use App\Application\Disbursement\VerifiedDestination;
use App\Infrastructure\Disbursement\EloquentDisbursementStore;
use App\Infrastructure\Disbursement\SyntheticDisbursementSources;
use App\Models\Disbursement;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\Events\TransactionBeginning;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\Support\DisbursementFixture;

/*
 * Lock order at the adapter boundary (#96 5874488658). Within each outermost transaction, no
 * source that may lock Business, User or Party rows (funding, destinations) is called while a
 * disbursement row is locked without the Business lock taken first, and the destination source is
 * never called under a disbursement lock at all.
 */

final class LockOrderProbe
{
    public bool $business = false;

    public bool $disbursement = false;

    /** @var list<string> */
    public array $calls = [];

    /** @var list<string> */
    public array $violations = [];

    public function reset(): void
    {
        $this->business = false;
        $this->disbursement = false;
    }

    public function call(string $name, bool $locksBusiness = false): void
    {
        $this->calls[] = $name;
        if ($this->disbursement && ! $this->business) {
            $this->violations[] = $name.' under a disbursement lock without the Business lock';
        }
        if ($this->disbursement && $name === 'verified') {
            $this->violations[] = $name.' under a disbursement lock';
        }
        $this->business = $this->business || $locksBusiness;
    }
}

function lockOrderProbe(): LockOrderProbe
{
    $probe = new LockOrderProbe;
    $sources = app(SyntheticDisbursementSources::class);
    app()->instance(FundedCampaigns::class, new class($sources, $probe) implements FundedCampaigns
    {
        public function __construct(private FundedCampaigns $inner, private LockOrderProbe $probe) {}

        public function funded(?string $before, int $limit): array
        {
            $this->probe->call('funded');

            return $this->inner->funded($before, $limit);
        }

        public function lockBusiness(string $businessId): void
        {
            $this->probe->call('lockBusiness', true);
            $this->inner->lockBusiness($businessId);
        }

        public function lockFunded(string $campaignId): FundedCampaign
        {
            $this->probe->call('lockFunded');

            return $this->inner->lockFunded($campaignId);
        }

        public function recheck(FundedCampaign $campaign): RecheckResult
        {
            $this->probe->call('recheck');

            return $this->inner->recheck($campaign);
        }

        public function issue(FundedCampaign $campaign, IssueInstruction $instruction): void
        {
            $this->probe->call('issue');
            $this->inner->issue($campaign, $instruction);
        }

        public function failClose(FundedCampaign $campaign, FailedClosing $closing): void
        {
            $this->probe->call('failClose');
            $this->inner->failClose($campaign, $closing);
        }
    });
    app()->instance(PayoutDestinations::class, new class($sources, $probe) implements PayoutDestinations
    {
        public function __construct(private PayoutDestinations $inner, private LockOrderProbe $probe) {}

        public function verified(string $businessId, string $environment): ?VerifiedDestination
        {
            $this->probe->call('verified');

            return $this->inner->verified($businessId, $environment);
        }
    });
    // RefreshDatabase keeps one base transaction open; each outermost application transaction is level 2.
    Event::listen(TransactionBeginning::class, function () use ($probe): void {
        if (DB::transactionLevel() === 2) {
            $probe->reset();
        }
    });
    Event::listen(QueryExecuted::class, function (QueryExecuted $query) use ($probe): void {
        if (preg_match('/from "disbursements" .*for update/i', $query->sql) === 1) {
            $probe->disbursement = true;
        }
    });

    return $probe;
}

it('never calls a Business-locking source under a disbursement lock on any path', function (): void {
    $probe = lockOrderProbe();
    ['disbursement' => $disbursement, 'intent' => $intent] = DisbursementFixture::paid();
    $treasury = DisbursementFixture::staff(['treasury']);
    DisbursementFixture::command($treasury, $disbursement, 'requery', 2);
    app(RecordPayoutEvent::class)->handle(DisbursementFixture::provider()->callback($intent->id, 'pending'));
    DisbursementFixture::provider()->scriptQuery($intent->id, 'succeeded');
    app(ReconcileDisbursements::class)->handle();
    app(ManageDisbursements::class)->page($treasury->id, $disbursement->id, null, 25);

    ['disbursement' => $waiting] = DisbursementFixture::funded();
    $compliance = DisbursementFixture::staff(['compliance']);
    DisbursementFixture::command($compliance, $waiting, 'hold', 0);
    DisbursementFixture::command($compliance, $waiting, 'release_hold', 1);

    $failing = DisbursementFixture::sources()->fund();
    DisbursementFixture::sources()->scriptRecheck($failing->campaignId, 'failed', ['mandate']);
    expect(app(OpenFundedDisbursements::class)->handle()['failed'])->toBe(1);

    expect($probe->violations)->toBe([])
        ->and($probe->calls)->toContain('funded', 'lockBusiness', 'lockFunded', 'verified', 'recheck', 'issue', 'failClose');
});

it('detects a source called under a disbursement lock, so its silence means something', function (): void {
    $probe = lockOrderProbe();
    ['disbursement' => $disbursement] = DisbursementFixture::funded();
    DB::transaction(function () use ($disbursement): void {
        Disbursement::query()->whereKey($disbursement->id)->lockForUpdate()->sole();
        app(PayoutDestinations::class)->verified($disbursement->business_id, $disbursement->environment);
    });
    expect($probe->violations)->toBe(['verified under a disbursement lock without the Business lock', 'verified under a disbursement lock']);
});

it('takes the one provider event identity lock before the identity lookup on every observation path', function (): void {
    ['disbursement' => $disbursement, 'intent' => $intent] = DisbursementFixture::paid();
    $treasury = DisbursementFixture::staff(['treasury']);
    $key = EloquentDisbursementStore::providerEventLockKey('synthetic', 'observed-everywhere');
    $trace = [];
    DB::listen(function (QueryExecuted $query) use (&$trace, $key): void {
        if (str_contains($query->sql, 'pg_advisory_xact_lock') && in_array($key, $query->bindings, true)) {
            $trace[] = 'identity-lock';
        } elseif (str_contains($query->sql, 'from "disbursement_provider_events"') && str_contains($query->sql, '"disposition" not in')) {
            $trace[] = 'identity-lookup';
        }
    });
    $observe = function (string $path) use (&$trace, $intent, $disbursement, $treasury): array {
        $trace = [];
        match ($path) {
            'callback' => app(RecordPayoutEvent::class)->handle(DisbursementFixture::provider()->callback($intent->id, 'pending',
                ['event_id' => 'observed-everywhere', 'observed_at' => '2026-09-28T10:00:00+00:00'])),
            'query' => (function () use ($intent): void {
                DisbursementFixture::provider()->scriptQuery($intent->id, 'pending', ['event_id' => 'observed-everywhere', 'observed_at' => '2026-09-28T10:01:00+00:00']);
                app(ReconcileDisbursements::class)->handle();
            })(),
            default => (function () use ($intent, $treasury, $disbursement): void {
                DisbursementFixture::provider()->scriptQuery($intent->id, 'pending', ['event_id' => 'observed-everywhere', 'observed_at' => '2026-09-28T10:02:00+00:00']);
                DisbursementFixture::command($treasury, $disbursement, 'requery', 2);
            })(),
        };

        return $trace;
    };

    expect($observe('callback'))->toBe(['identity-lock', 'identity-lookup'])
        ->and($observe('query'))->toBe(['identity-lock', 'identity-lookup'])
        ->and($observe('requery'))->toBe(['identity-lock', 'identity-lookup']);
});
