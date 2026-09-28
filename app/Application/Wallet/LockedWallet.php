<?php

declare(strict_types=1);

namespace App\Application\Wallet;

/**
 * A wallet row locked FOR UPDATE in the caller's open transaction by `WalletPostings::lockForParty`.
 * Every posting locks it again and checks it still belongs to this Party.
 */
final readonly class LockedWallet
{
    public function __construct(public string $walletId, public string $partyId) {}
}
