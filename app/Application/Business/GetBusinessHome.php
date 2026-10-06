<?php

declare(strict_types=1);

namespace App\Application\Business;

use App\Application\Business\Contracts\BusinessApplicationStore;
use App\Application\Business\Contracts\BusinessCampaignStore;
use App\Application\Wallet\GetBusinessWallet;

/**
 * Business Home for a current mandate holder. Each read runs under current Business authority:
 * the landing entry and verified profile, the Business's own raises from retained publication,
 * closure and funding facts, and the wallet figures. Nothing here computes a rating, headroom or
 * a due repayment that no retained source holds.
 */
final class GetBusinessHome
{
    public function __construct(private BusinessApplicationStore $applications, private BusinessCampaignStore $campaigns, private GetBusinessWallet $wallet) {}

    /** @return array<string, mixed> */
    public function handle(int $userId, int $contextRevision, string $businessId): array
    {
        return ['identity_context_revision' => $contextRevision, 'entry' => $this->applications->home($userId, $contextRevision, $businessId),
            'raises' => $this->campaigns->home($userId, $contextRevision, $businessId), 'wallet' => $this->wallet->summary($userId, $contextRevision, $businessId)];
    }
}
