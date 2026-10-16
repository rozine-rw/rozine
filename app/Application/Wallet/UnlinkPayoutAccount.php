<?php

declare(strict_types=1);

namespace App\Application\Wallet;

use App\Application\Wallet\Contracts\PayoutAccounts;

/**
 * `wallet.unlink_method`: revokes one of the Investor's linked accounts. Nothing new can be paid from
 * or to it; deposits and withdrawals already made with it keep it on their record.
 */
final class UnlinkPayoutAccount
{
    public function __construct(private PayoutAccounts $accounts) {}

    /** @return array<string, mixed> */
    public function handle(int $userId, string $requestId, string $methodId): array
    {
        return $this->accounts->unlink($userId, $requestId, $methodId);
    }
}
