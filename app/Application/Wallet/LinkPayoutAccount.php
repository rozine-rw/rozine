<?php

declare(strict_types=1);

namespace App\Application\Wallet;

use App\Application\Wallet\Contracts\PayoutAccounts;

/**
 * `wallet.link_method`: links an MTN MoMo, Airtel Money or bank account once it is verified as the
 * Investor's own (BRS v2.0 KYC-R02). From then on deposits come from it and withdrawals go to it.
 */
final class LinkPayoutAccount
{
    public function __construct(private PayoutAccounts $accounts) {}

    /**
     * @param  array{type?: mixed, network?: mixed, bank?: mixed, number?: mixed}  $input
     * @return array<string, mixed>
     */
    public function handle(int $userId, string $requestId, array $input): array
    {
        return $this->accounts->link($userId, $requestId, $input);
    }
}
