<?php

declare(strict_types=1);

namespace App\Application\Wallet;

use App\Application\Wallet\Contracts\BusinessWalletStore;

/**
 * `business.wallet.deposit`: records the intent, its receipt and its queued dispatch outbox row in one
 * transaction under the actor's current mandate permission. Nothing is credited here; only a
 * verified provider success can credit, later and once.
 */
final class RecordBusinessDeposit
{
    public function __construct(private BusinessWalletStore $store) {}

    /**
     * @param  array{currency: string, amount: string}  $amount
     * @return array<string, mixed>
     */
    public function handle(int $userId, int $contextRevision, string $businessId, string $requestId, array $amount, string $methodId): array
    {
        return $this->store->deposit($userId, $contextRevision, $businessId, $requestId, $amount, $methodId);
    }
}
