<?php

declare(strict_types=1);

namespace App\Application\Wallet;

use App\Application\Wallet\Contracts\BusinessWalletStore;

/**
 * The Business wallet's statement for a period (design wallet history download), read under current
 * Business authority and laid out as the requested download.
 */
final class GetBusinessWalletStatement
{
    public function __construct(private BusinessWalletStore $store) {}

    public function handle(int $userId, int $contextRevision, string $businessId, ?string $from, ?string $to): WalletStatementDocument
    {
        return new WalletStatementDocument($this->store->statement($userId, $contextRevision, $businessId, $from, $to));
    }
}
