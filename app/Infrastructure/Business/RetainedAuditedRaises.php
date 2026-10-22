<?php

declare(strict_types=1);

namespace App\Infrastructure\Business;

use App\Application\Business\Contracts\AuditedRaises;
use App\Application\Business\Contracts\CampaignClosureEvidence;
use App\Application\Business\Contracts\InvestorDealCatalogue;
use App\Application\Business\Contracts\PublishedCampaignEvidence;
use App\Models\BusinessCampaign;

/**
 * Reads each raise through the Investor card and its verified closure evidence, so the partner sees
 * the same raised amount and investor count an Investor does. A raise without a published rating
 * has no card and is left out.
 *
 * @phpstan-import-type Raise from AuditedRaises
 */
final class RetainedAuditedRaises implements AuditedRaises
{
    public function __construct(private InvestorDealCatalogue $deals, private PublishedCampaignEvidence $publications, private CampaignClosureEvidence $closures) {}

    /** @return list<Raise> */
    public function unfunded(array $applicationIds, int $limit): array
    {
        $raises = [];
        foreach (BusinessCampaign::query()->whereIn('business_application_id', $applicationIds)->orderByDesc('live_at')->orderByDesc('id')->limit($limit)->pluck('id') as $campaignId) {
            $card = $this->deals->card($campaignId);
            $closure = $this->closures->find($campaignId);
            if ($card === null || ($closure === null && $card['lifecycle'] === 'funded')) {
                continue;
            }
            $raises[] = ['campaign_id' => $campaignId, 'business' => (string) $card['name'], 'title' => (string) $this->publications->find($campaignId)['title'],
                'status' => $closure === null ? 'live' : 'failed', 'raised' => (string) ($closure['committed_refunded']['amount'] ?? $card['raised']['amount']),
                'target' => (string) $card['target']['amount'], 'investors' => (int) ($closure['investors'] ?? $card['investors'])];
        }

        return $raises;
    }
}
