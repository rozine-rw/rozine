<?php

declare(strict_types=1);

namespace App\Application\Wallet;

use App\Application\Wallet\Contracts\BusinessWalletStore;

/** The Business wallet page facts for a current mandate holder, read under current Business authority. */
final class GetBusinessWallet
{
    public function __construct(private BusinessWalletStore $store) {}

    /**
     * @param  array{kind?: string|null, amount?: string|null, movement?: string|null, before?: string|null, receipt?: string|null}  $query
     * @return array<string, mixed>
     */
    public function handle(int $userId, int $contextRevision, string $businessId, array $query = []): array
    {
        return $this->store->page($userId, $contextRevision, $businessId, $query);
    }
}
