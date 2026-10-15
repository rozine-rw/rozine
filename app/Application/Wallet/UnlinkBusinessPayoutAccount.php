<?php

declare(strict_types=1);

namespace App\Application\Wallet;

use App\Application\Wallet\Contracts\BusinessPayoutAccounts;

/**
 * `business.wallet.unlink_method`: revokes one of the Business's linked accounts. Nothing new can be
 * paid from or to it; deposits and withdrawals already made with it keep it on their record.
 */
final class UnlinkBusinessPayoutAccount
{
    public function __construct(private BusinessPayoutAccounts $accounts) {}

    /** @return array<string, mixed> */
    public function handle(int $userId, int $contextRevision, string $businessId, string $requestId, string $methodId): array
    {
        return $this->accounts->unlink($userId, $contextRevision, $businessId, $requestId, $methodId);
    }
}
