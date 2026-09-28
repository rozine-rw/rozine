<?php

declare(strict_types=1);

namespace App\Application\Wallet;

use App\Application\Wallet\Contracts\WalletStore;

/** The Investor wallet page facts for the authenticated investor, read under current authority. */
final class GetInvestorWallet
{
    public function __construct(private WalletStore $store) {}

    /**
     * @param  array{kind?: string|null, amount?: string|null, movement?: string|null, before?: string|null, receipt?: string|null}  $query
     * @return array<string, mixed>
     */
    public function handle(int $userId, ?int $contextRevision, array $query = []): array
    {
        return $this->store->page($userId, $contextRevision, $query);
    }
}
