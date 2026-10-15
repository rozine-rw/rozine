<?php

declare(strict_types=1);

namespace App\Application\Wallet;

use App\Application\Wallet\Contracts\BusinessPayoutAccounts;

/**
 * `business.wallet.link_method`: links an MTN MoMo, Airtel Money or bank account once it is verified
 * as held in the Business's registered name (BRS v2.0 KYC-R03). From then on the Business deposits
 * from it and withdraws to it.
 */
final class LinkBusinessPayoutAccount
{
    public function __construct(private BusinessPayoutAccounts $accounts) {}

    /**
     * @param  array{type?: mixed, network?: mixed, bank?: mixed, number?: mixed}  $input
     * @return array<string, mixed>
     */
    public function handle(int $userId, int $contextRevision, string $businessId, string $requestId, array $input): array
    {
        return $this->accounts->link($userId, $contextRevision, $businessId, $requestId, $input);
    }
}
