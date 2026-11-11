<?php

declare(strict_types=1);

namespace App\Application\Business;

use App\Application\Business\Contracts\BusinessProspectuses;
use App\Application\Business\Contracts\InvestorDealCatalogue;
use App\Application\Primary\GetInvestorViewer;
use App\Domain\Operations\CommandRejection;

/**
 * The signed prospectus behind a deal, as an Investor reads it before paying (BRS v3.0 PRO-R04,
 * KYC-R05). Whoever may read the Deals deck may read it; it stays readable after the raise closes,
 * since the notes it describes outlive the campaign.
 *
 * @phpstan-import-type Inputs from \App\Domain\Business\ProspectusGates
 * @phpstan-import-type SignedSummary from BusinessProspectuses
 */
final class GetInvestorProspectus
{
    public function __construct(private GetInvestorViewer $viewer, private InvestorDealCatalogue $catalogue, private BusinessProspectuses $prospectuses) {}

    /**
     * @return array{summary: SignedSummary, inputs: Inputs,
     *     facts: array{principal: string, term_months: int, instalment: string, verified_monthly_revenue: string, latest_sealed_report_at: string|null},
     *     quoted_lines: list<int>, contracted_months: list<string>, campaign_id: string, business_name: string}
     */
    public function handle(int $userId, string $campaignId): array
    {
        $this->viewer->admit($userId);
        $card = $this->catalogue->card($campaignId) ?? throw new CommandRejection('DEAL_NOT_FOUND', 404);
        $prospectus = $this->prospectuses->published($campaignId) ?? throw new CommandRejection('PROSPECTUS_NOT_FOUND', 404);

        return [...$prospectus, 'campaign_id' => $campaignId, 'business_name' => (string) $card['name']];
    }
}
