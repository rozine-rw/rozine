<?php

declare(strict_types=1);

namespace App\Application\Wallet;

use App\Application\Wallet\Contracts\WalletStore;

/**
 * The authenticated Investor's wallet statement for a period, read under current authority and
 * laid out as the requested download.
 */
final class GetInvestorWalletStatement
{
    public function __construct(private WalletStore $store) {}

    public function handle(int $userId, ?int $contextRevision, ?string $from, ?string $to): WalletStatementDocument
    {
        return new WalletStatementDocument($this->store->statement($userId, $contextRevision, $from, $to));
    }
}
