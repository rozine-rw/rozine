<?php

declare(strict_types=1);

namespace App\Infrastructure\Primary;

use App\Application\Operations\Contracts\ChangeFeed;
use App\Application\Primary\Contracts\CampaignFundingEvidence;
use App\Application\Primary\Contracts\FundedPurchaseChanges;
use App\Domain\Operations\ChangeScope;

/**
 * The funded purchases come from the campaign's retained funding record. A synthetic disbursement
 * source pays out a campaign no Investor bought into, so with no record there is nobody to tell.
 */
final readonly class RetainedFundedPurchaseChanges implements FundedPurchaseChanges
{
    public function __construct(private CampaignFundingEvidence $fundings, private ChangeFeed $changes) {}

    public function record(string $campaignId): void
    {
        /** @var array{commitments: list<array{party_id: string, reservation_id: string}>}|null $funding */
        $funding = $this->fundings->find($campaignId);
        foreach ($funding['commitments'] ?? [] as $purchase) {
            $this->changes->record(ChangeScope::party($purchase['party_id']), 'purchase', $purchase['reservation_id']);
        }
    }
}
