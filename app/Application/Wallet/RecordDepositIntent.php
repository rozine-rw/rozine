<?php

declare(strict_types=1);

namespace App\Application\Wallet;

use App\Application\Wallet\Contracts\WalletStore;

/**
 * `wallet.deposit`: records the intent, its receipt and its dispatch outbox row in one transaction.
 * Nothing is credited here; only a verified provider success can credit, later and once.
 */
final class RecordDepositIntent
{
    public function __construct(private WalletStore $store) {}

    /**
     * @param  array{currency: string, amount: string}  $amount
     * @return array<string, mixed>
     */
    public function handle(int $userId, int $contextRevision, string $requestId, array $amount, string $methodId): array
    {
        return $this->store->deposit($userId, $contextRevision, $requestId, $amount, $methodId);
    }
}
