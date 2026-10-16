<?php

declare(strict_types=1);

namespace App\Application\Wallet\Contracts;

use App\Application\Wallet\PostingSource;
use App\Domain\Wallet\WalletMoney;

/**
 * Historical original hold/commit authentication for retained funding and settlement reads.
 * Verifies the exact wallet owner, immutable receipt digests/envelopes and relational movements.
 * Takes no locks, writes or balance reads; later issue/refund is a separate current-state gate.
 * This evidence never authorizes new funding, current admission, a refund or a payout.
 */
interface PrimaryCashReceipts
{
    public function verify(string $partyId, string $walletId, WalletMoney $principal, PostingSource $source, string $holdId, string $commitId): void;
}
