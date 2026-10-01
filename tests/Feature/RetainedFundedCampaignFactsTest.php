<?php

declare(strict_types=1);

use App\Application\Business\Contracts\PublishedCampaignEvidence;
use App\Application\Disbursement\Contracts\FundedCampaigns;
use App\Application\Primary\Contracts\CampaignFundingEvidence;
use App\Domain\Disbursement\IntentDigest;
use App\Infrastructure\Business\RetainedFundedCampaignFacts;
use App\Models\BusinessProfile;
use App\Models\LedgerEntry;
use App\Models\PrimaryCampaignFunding;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Tests\Support\PrimaryHoldingFixture;

beforeEach(fn () => $this->freezeSecond());

/** @param array<string, mixed> $payload */
function retainedFundingFactsStub(array $payload): CampaignFundingEvidence
{
    return new class($payload) implements CampaignFundingEvidence
    {
        /** @param array<string, mixed> $payload */
        public function __construct(private array $payload) {}

        public function find(string $campaignId): array
        {
            return $this->payload;
        }
    };
}

it('returns no funded facts for a fully committed campaign before retained funding exists', function (): void {
    ['campaign' => $campaign] = PrimaryHoldingFixture::committed(fund: false);
    $binding = app(FundedCampaigns::class);
    $queries = [];
    DB::listen(function (QueryExecuted $query) use (&$queries): void {
        $queries[] = $query->sql;
    });
    expect(app(RetainedFundedCampaignFacts::class)->find($campaign->id))->toBeNull()
        ->and($queries)->toHaveCount(1)->and($queries[0])->toContain('primary_campaign_fundings')
        ->and(app(FundedCampaigns::class))->toBe($binding);
});

it('projects exact immutable purchases and terms in commitment order without locks or writes', function (): void {
    ['campaign' => $campaign] = PrimaryHoldingFixture::committed(['540', '540', '1080'], requoted: [1], buyers: [0 => 'same', 1 => 'same']);
    $retained = app(CampaignFundingEvidence::class)->find($campaign->id);
    $source = retainedFundingFactsStub([...$retained, 'commitments' => array_reverse($retained['commitments'])]);
    $projection = new RetainedFundedCampaignFacts($source, app(PublishedCampaignEvidence::class));
    $queries = [];
    DB::listen(function (QueryExecuted $query) use (&$queries): void {
        $queries[] = $query->sql;
    });
    $facts = $projection->find($campaign->id);
    $expected = array_map(fn (array $purchase): array => ['id' => $purchase['commitment_id'], 'party_id' => $purchase['party_id'],
        'origin_operation_id' => $purchase['cash']['origin_operation_id'], 'units' => (int) $purchase['units'],
        'ordinals' => $purchase['ordinals'], 'rights' => $purchase['rights'], 'terms' => $purchase['terms'], 'principal' => $purchase['principal']], $retained['commitments']);
    usort($expected, fn (array $left, array $right): int => strcmp($left['id'], $right['id']));
    expect(array_map(fn ($commitment): array => $commitment->toArray(), $facts->commitments))->toBe($expected)
        ->and($facts->campaignId)->toBe($campaign->id)->and($facts->businessId)->toBe($campaign->business_id)
        ->and($facts->businessName)->toBe(BusinessProfile::query()->whereKey($campaign->business_id)->sole()->profile['name'])
        ->and($facts->title)->toBe($campaign->payload['title'])->and($facts->exposureReservationId)->toBe($campaign->exposure_reservation_id)
        ->and($facts->principal)->toBe($campaign->principal)->and($facts->fundedAt)->toBe($retained['recorded_at'])
        ->and($facts->termMonths)->toBe($campaign->payload['quote']['term_months'])->and($facts->partyIds())->toHaveCount(2)
        ->and($facts->commitmentsDigest())->toBe(IntentDigest::commitments($expected));
    foreach ($queries as $query) {
        expect(strtolower($query))->toStartWith('select')->not->toContain('for update', 'for share');
    }
});

it('reads identical retained facts after the complete Holding and cash issue set', function (): void {
    ['campaign' => $campaign, 'commitments' => $commitments] = PrimaryHoldingFixture::committed();
    $source = app(RetainedFundedCampaignFacts::class);
    $before = $source->find($campaign->id);
    $closing = PrimaryHoldingFixture::issuedClosing($campaign);
    foreach ($commitments as $commitment) {
        PrimaryHoldingFixture::insert($commitment->id, $closing);
        PrimaryHoldingFixture::issue($commitment->id, $closing);
    }
    PrimaryHoldingFixture::flushDeferredChecks();
    $cash = LedgerEntry::query()->orderBy('id')->get()->toJson();
    expect(LedgerEntry::query()->where('kind', 'primary_issue')->count())->toBe(2)
        ->and($source->find($campaign->id))->toEqual($before)
        ->and(LedgerEntry::query()->orderBy('id')->get()->toJson())->toBe($cash);
});

it('refuses misbound source facts before they become a funded campaign', function (string $damage): void {
    ['campaign' => $campaign] = PrimaryHoldingFixture::committed();
    $retained = app(CampaignFundingEvidence::class)->find($campaign->id);
    if ($damage === 'term') {
        $retained['commitments'][0]['terms']['term_months']++;
    } else {
        $retained[$damage] = 'foreign';
    }
    $source = retainedFundingFactsStub($retained);
    expect(fn () => (new RetainedFundedCampaignFacts($source, app(PublishedCampaignEvidence::class)))->find($campaign->id))
        ->toThrow(RuntimeException::class, 'PRIMARY_FUNDING_INTEGRITY_FAILED');
})->with(['campaign_id', 'business_id', 'exposure_reservation_id', 'principal', 'publication_sha256', 'term']);

it('propagates retained funding integrity refusal without using its unauthenticated payload', function (): void {
    ['campaign' => $campaign] = PrimaryHoldingFixture::committed();
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('ALTER TABLE primary_campaign_fundings DISABLE TRIGGER primary_funding_immutable');
    try {
        PrimaryCampaignFunding::query()->where('business_campaign_id', $campaign->id)->update(['sha256' => str_repeat('0', 64)]);
    } finally {
        DB::statement('ALTER TABLE primary_campaign_fundings ENABLE TRIGGER primary_funding_immutable');
    }
    expect(fn () => app(RetainedFundedCampaignFacts::class)->find($campaign->id))->toThrow(RuntimeException::class, 'PRIMARY_FUNDING_INTEGRITY_FAILED');
});

it('refuses a publication without a typed retained tenor', function (): void {
    ['campaign' => $campaign] = PrimaryHoldingFixture::committed();
    $publication = app(PublishedCampaignEvidence::class)->find($campaign->id);
    $publication['quote']['term_months'] = (string) $publication['quote']['term_months'];
    $source = new class($publication) implements PublishedCampaignEvidence
    {
        /** @param array<string, mixed> $payload */
        public function __construct(private array $payload) {}

        public function find(string $campaignId): array
        {
            return $this->payload;
        }
    };
    expect(fn () => (new RetainedFundedCampaignFacts(app(CampaignFundingEvidence::class), $source))->find($campaign->id))
        ->toThrow(RuntimeException::class, 'PRIMARY_FUNDING_INTEGRITY_FAILED');
});
