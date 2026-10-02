<?php

declare(strict_types=1);

use App\Application\Disbursement\Contracts\DisbursementStore;
use App\Application\Disbursement\Contracts\FundedCampaigns;
use App\Application\Disbursement\FailedClosing;
use App\Application\Disbursement\FundedCampaign;
use App\Application\Disbursement\IssueInstruction;
use App\Application\Primary\Contracts\CampaignFundingEvidence;
use App\Domain\Disbursement\DisbursementViolation;
use App\Domain\Operations\CommandRejection;
use App\Infrastructure\Business\RetainedFundedCampaignFacts;
use App\Infrastructure\Primary\EloquentFundedCampaigns;
use App\Models\Disbursement;
use App\Models\PrimaryCampaignFunding;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\PrimaryHoldingFixture;

beforeEach(fn () => $this->freezeSecond());

it('feeds real retained facts to the existing observation driver only when explicitly test-bound', function (): void {
    ['campaign' => $campaign] = PrimaryHoldingFixture::committed(requoted: [0]);
    $facts = app(RetainedFundedCampaignFacts::class)->find($campaign->id);
    if (! $facts instanceof FundedCampaign) {
        throw new LogicException('Retained funded campaign was not available.');
    }
    $before = [DB::table('ledger_entries')->count(), DB::table('primary_holdings')->count(), DB::table('business_campaign_closures')->count()];
    app()->instance(FundedCampaigns::class, app(EloquentFundedCampaigns::class));
    expect(app(DisbursementStore::class)->openFunded(10))->toBe(['opened' => 1, 'known' => 0])
        ->and(app(DisbursementStore::class)->openFunded(10))->toBe(['opened' => 0, 'known' => 1]);
    $observation = Disbursement::query()->where('business_campaign_id', $campaign->id)->sole();
    expect($observation->business_id)->toBe($facts->businessId)
        ->and($observation->exposure_reservation_id)->toBe($facts->exposureReservationId)
        ->and($observation->amount)->toBe($facts->principal)
        ->and($observation->commitments_digest)->toBe($facts->commitmentsDigest())
        ->and($observation->commitment_count)->toBe(count($facts->commitments))
        ->and(DB::table('disbursement_closings')->count())->toBe(0)
        ->and([DB::table('ledger_entries')->count(), DB::table('primary_holdings')->count(), DB::table('business_campaign_closures')->count()])->toBe($before);
});

it('lists only authenticated retained fundings newest first with a strict campaign cursor and limit', function (): void {
    ['campaign' => $first] = PrimaryHoldingFixture::committed();
    $this->travel(1)->seconds();
    ['campaign' => $second] = PrimaryHoldingFixture::committed();
    PrimaryHoldingFixture::committed(fund: false);
    $adapter = app(EloquentFundedCampaigns::class);
    $queries = [];
    DB::listen(function (QueryExecuted $query) use (&$queries): void {
        $queries[] = strtolower($query->sql);
    });
    $refs = $adapter->funded(null, 1);
    $next = $adapter->funded($refs[0]->campaignId, 1);
    expect(array_map(fn ($ref): string => $ref->campaignId, $refs))->toBe([$second->id])
        ->and(array_map(fn ($ref): string => $ref->campaignId, $next))->toBe([$first->id])
        ->and($refs[0]->businessId)->toBe($second->business_id)
        ->and($refs[0]->fundedAt)->toBe(app(CampaignFundingEvidence::class)->find($second->id)['recorded_at'])
        ->and($adapter->funded($first->id, 1))->toBe([])->and($adapter->funded(null, 0))->toBe([]);
    foreach ($queries as $query) {
        expect($query)->toStartWith('select')->not->toContain('for update', 'for share');
    }
});

it('locks only Business then campaign in the caller transaction before reading exact retained facts', function (): void {
    ['campaign' => $campaign] = PrimaryHoldingFixture::committed(requoted: [0]);
    $expected = app(RetainedFundedCampaignFacts::class)->find($campaign->id);
    $queries = [];
    DB::listen(function (QueryExecuted $query) use (&$queries): void {
        $queries[] = strtolower($query->sql);
    });
    $read = DB::transaction(function () use ($campaign): FundedCampaign {
        $adapter = app(EloquentFundedCampaigns::class);
        $adapter->lockBusiness($campaign->business_id);

        return $adapter->lockFunded($campaign->id);
    });
    expect($read)->toEqual($expected);
    $locks = array_values(array_filter($queries, fn (string $sql): bool => str_contains($sql, 'for update')));
    expect($locks)->toHaveCount(2)->and($locks[0])->toContain('business_profiles')
        ->and($locks[1])->toContain('business_campaigns');
    foreach ($queries as $query) {
        expect($query)->toStartWith('select');
    }
});

it('refuses absent Business campaign or retained funding under the caller gates', function (string $missing): void {
    ['campaign' => $campaign] = PrimaryHoldingFixture::committed(fund: false);
    expect(fn () => DB::transaction(function () use ($missing, $campaign): void {
        $adapter = app(EloquentFundedCampaigns::class);
        $adapter->lockBusiness($missing === 'business' ? strtolower((string) Str::ulid()) : $campaign->business_id);
        $adapter->lockFunded($missing === 'campaign' ? strtolower((string) Str::ulid()) : $campaign->id);
    }))->toThrow(CommandRejection::class, 'FUNDING_SOURCE_UNAVAILABLE');
})->with(['business', 'campaign', 'funding']);

it('propagates retained corruption during discovery and locking instead of listing unauthenticated references', function (string $operation): void {
    ['campaign' => $campaign] = PrimaryHoldingFixture::committed();
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('ALTER TABLE primary_campaign_fundings DISABLE TRIGGER primary_funding_immutable');
    try {
        PrimaryCampaignFunding::query()->where('business_campaign_id', $campaign->id)->update(['sha256' => str_repeat('0', 64)]);
    } finally {
        DB::statement('ALTER TABLE primary_campaign_fundings ENABLE TRIGGER primary_funding_immutable');
    }
    expect(fn () => DB::transaction(function () use ($campaign, $operation): mixed {
        $adapter = app(EloquentFundedCampaigns::class);
        if ($operation === 'discovery') {
            return $adapter->funded(null, 1);
        }
        $adapter->lockBusiness($campaign->business_id);

        return $adapter->lockFunded($campaign->id);
    }))->toThrow(RuntimeException::class, 'PRIMARY_FUNDING_INTEGRITY_FAILED');
})->with(['discovery', 'locking']);

it('keeps current admission unavailable and all financial writes refused despite genuine retained funding', function (): void {
    ['campaign' => $campaign] = PrimaryHoldingFixture::committed();
    $binding = app(FundedCampaigns::class);
    $before = [DB::table('ledger_entries')->count(), DB::table('primary_holdings')->count(), DB::table('business_campaign_closures')->count()];
    DB::transaction(function () use ($campaign): void {
        $adapter = app(EloquentFundedCampaigns::class);
        $adapter->lockBusiness($campaign->business_id);
        $facts = $adapter->lockFunded($campaign->id);
        $check = $adapter->recheck($facts);
        expect($check->outcome)->toBe('unavailable')->and($check->causes)->toContain('policy', 'restriction', 'mandate', 'evidence');
        $id = strtolower((string) Str::ulid());
        expect(fn () => $adapter->issue($facts, new IssueInstruction($id, $id, $id, now()->toIso8601String(), now()->toDateString(), [], now()->toIso8601String())))
            ->toThrow(CommandRejection::class, 'FUNDING_SOURCE_UNAVAILABLE');
        expect(fn () => $adapter->failClose($facts, new FailedClosing($id, $id, 'approve_recheck', ['restriction'])))
            ->toThrow(CommandRejection::class, 'FUNDING_SOURCE_UNAVAILABLE');
    });
    expect([DB::table('ledger_entries')->count(), DB::table('primary_holdings')->count(), DB::table('business_campaign_closures')->count()])->toBe($before)
        ->and(app(FundedCampaigns::class))->toBe($binding);
});

it('requires a caller transaction for every locking recheck or financial operation', function (string $operation): void {
    ['campaign' => $campaign] = PrimaryHoldingFixture::committed();
    $facts = app(RetainedFundedCampaignFacts::class)->find($campaign->id);
    if (! $facts instanceof FundedCampaign) {
        throw new LogicException('Retained funded campaign was not available.');
    }
    $adapter = app(EloquentFundedCampaigns::class);
    $id = strtolower((string) Str::ulid());
    expect(fn () => match ($operation) {
        'business' => $adapter->lockBusiness($facts->businessId),
        'campaign' => $adapter->lockFunded($facts->campaignId),
        'recheck' => $adapter->recheck($facts),
        'issue' => $adapter->issue($facts, new IssueInstruction($id, $id, $id, now()->toIso8601String(), now()->toDateString(), [], now()->toIso8601String())),
        'fail' => $adapter->failClose($facts, new FailedClosing($id, $id, 'approve_recheck', ['restriction'])),
        default => throw new InvalidArgumentException('Unknown operation.'),
    })->toThrow(DisbursementViolation::class, 'FUNDING_TRANSACTION_REQUIRED');
})->with(['business', 'campaign', 'recheck', 'issue', 'fail']);

it('reads the same historical funded campaign after complete Holding and cash issue without enabling admission', function (): void {
    ['campaign' => $campaign, 'commitments' => $commitments] = PrimaryHoldingFixture::committed();
    $adapter = app(EloquentFundedCampaigns::class);
    $before = $adapter->funded(null, 1);
    $closing = PrimaryHoldingFixture::issuedClosing($campaign);
    foreach ($commitments as $commitment) {
        PrimaryHoldingFixture::insert($commitment->id, $closing);
        PrimaryHoldingFixture::issue($commitment->id, $closing);
    }
    PrimaryHoldingFixture::flushDeferredChecks();
    expect($adapter->funded(null, 1))->toEqual($before);
});
