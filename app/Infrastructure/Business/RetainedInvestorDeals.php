<?php

declare(strict_types=1);

namespace App\Infrastructure\Business;

use App\Application\Business\Contracts\CampaignClosureEvidence;
use App\Application\Business\Contracts\InvestorDealCatalogue;
use App\Application\Business\Contracts\PublishedCampaignEvidence;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Models\BusinessApplicationQuote;
use App\Models\BusinessCampaign;
use App\Models\BusinessCampaignClosure;
use Brick\Math\BigInteger;
use Brick\Math\RoundingMode;
use RuntimeException;

/**
 * Projects retained publications for Investors. Only the allowlisted aggregates leave this class:
 * the business name, industry and district, the published rating, the immutable accepted draft's
 * story and use of funds, average monthly revenue and the publication's own economics. No
 * address, company code, officer, statement, raw evidence or Auditor original is ever read out.
 */
final class RetainedInvestorDeals implements InvestorDealCatalogue
{
    private const int LIMIT = 50;

    private const int JUST_LISTED_HOURS = 72;

    private const array ACCENTS = ['green', 'blue', 'amber', 'purple', 'teal', 'magenta', 'ink'];

    private const array LIFECYCLES = ['live' => 'live', 'fully_reserved' => 'fully_reserved', 'inventory_unavailable' => 'fully_reserved',
        'sold_out_pending_settlement' => 'funded', 'closing_pending_settlement' => 'expired'];

    public function __construct(private PublishedCampaignEvidence $publications, private CampaignClosureEvidence $closures,
        private CampaignProgress $progress, private CanonicalJson $json) {}

    public function deals(): array
    {
        $ids = BusinessCampaign::query()->whereNotIn('id', BusinessCampaignClosure::query()->select('business_campaign_id'))
            ->orderByDesc('live_at')->orderByDesc('id')->limit(self::LIMIT)->pluck('id');

        return array_values(array_filter(array_map(fn (string $id): ?array => $this->project($id, false), $ids->all())));
    }

    public function deal(string $campaignId): ?array
    {
        if (! BusinessCampaign::query()->whereKey($campaignId)->exists() || $this->closures->find($campaignId) !== null) {
            return null;
        }

        return $this->project($campaignId, true);
    }

    /** @return array<string, mixed>|null */
    private function project(string $campaignId, bool $detail): ?array
    {
        $payload = $this->publications->find($campaignId);
        $evidence = $payload['public_evidence'];
        $rating = $evidence['rating'] ?? null;
        $revenue = $evidence['totals']['revenue']['amount'] ?? null;
        $months = $evidence['period']['months'] ?? null;
        if ($rating === null || $revenue === null || ! is_int($months) || $months < 1) {
            return null;
        }
        $draft = $this->acceptedDraft($payload);
        $progress = $this->progress->project($campaignId, $payload)['progress'];
        $principal = BigInteger::of($payload['principal']);
        $total = $payload['quote']['units'];
        $funded = $progress['phase'] === 'funded';
        $raised = $progress['committed']['amount'];
        $card = ['campaign_id' => $campaignId, 'revision' => 1, 'name' => $evidence['business']['name'],
            'accent' => self::ACCENTS[hexdec(substr(hash('sha256', $payload['business_id']), 0, 6)) % count(self::ACCENTS)],
            'industry' => $evidence['business']['industry'], 'district' => $evidence['business']['district'],
            'rating' => ['band' => $rating['band'], 'score' => $rating['score']], 'audited' => ($payload['binding']['report'] ?? null) !== null,
            'just_listed' => now()->subHours(self::JUST_LISTED_HOURS)->lt($payload['recorded_at']), 'photos' => [],
            'raised' => ['currency' => 'RWF', 'amount' => $raised], 'target' => ['currency' => 'RWF', 'amount' => (string) $principal],
            'funded_pct' => $funded ? '100.0' : $progress['funded_pct'],
            'left_to_fill' => ['currency' => 'RWF', 'amount' => $funded ? '0' : $progress['remaining']['amount']],
            'avg_monthly_revenue' => ['currency' => 'RWF', 'amount' => (string) BigInteger::of($revenue)->dividedBy($months, RoundingMode::HalfUp)],
            'units' => $funded ? ['total' => $total, 'available' => '0', 'reserved' => '0', 'committed' => $total]
                : ['total' => $total, 'available' => $progress['units']['available'], 'reserved' => $progress['units']['reserved'], 'committed' => $progress['units']['committed']],
            'unit_price' => $payload['quote']['unit_price'], 'investors' => $progress['investors'],
            'lifecycle' => $funded ? 'funded' : self::LIFECYCLES[$progress['lifecycle']], 'restriction' => null,
            'clock' => ['starts_at' => $payload['recorded_at'], 'expires_at' => $payload['expires_at']],
            'listing_fee' => $payload['listing_fee'], 'story' => $draft['story'],
            'rate_pct' => $payload['quote']['rate_pct'], 'term_months' => $payload['quote']['term_months']];
        if (! $detail) {
            return $card;
        }

        return [...$card, 'use_of_funds' => $draft['use_of_funds'],
            'financials' => ['avg_monthly_revenue' => $card['avg_monthly_revenue'], 'ebitda' => ['value' => null, 'unavailable' => 'NOT_SOURCED_AS_EBITDA']],
            'rationale' => null, 'track_record' => null,
            'about' => ['description' => $draft['story'], 'industry' => $card['industry'], 'district' => $card['district']],
            'audit' => null, 'updates' => [], 'overdue_report' => null];
    }

    /**
     * The draft the Business accepted, from the quote the publication pins.
     *
     * @param  array<string, mixed>  $payload
     * @return array{story: string, use_of_funds: list<string>}
     */
    private function acceptedDraft(array $payload): array
    {
        $quote = BusinessApplicationQuote::query()->whereKey($payload['quote']['quote_id'])->first();
        if ($quote === null || ! hash_equals($quote->sha256, hash('sha256', $this->json->encode($quote->payload)))
            || $quote->payload['application_id'] !== $payload['application_id']) {
            throw new RuntimeException('CAMPAIGN_QUOTE_INTEGRITY_FAILED');
        }

        return ['story' => $quote->payload['draft']['story'], 'use_of_funds' => $quote->payload['draft']['use_of_funds']];
    }
}
