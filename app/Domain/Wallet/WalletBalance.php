<?php

declare(strict_types=1);

namespace App\Domain\Wallet;

/**
 * Ledger-derived buckets. `total` counts available, held and unissued committed once each; only
 * `available` is spendable. Pending deposits are not credited and so sit outside `total`.
 */
final readonly class WalletBalance
{
    public function __construct(
        public WalletMoney $available,
        public WalletMoney $held,
        public WalletMoney $committed,
        public WalletMoney $pendingDeposits,
    ) {}

    /**
     * An account bucket's balance from its postings: Investor buckets are liabilities, so credits
     * add and debits subtract. A bucket can never be overdrawn.
     */
    public static function bucket(WalletMoney $credits, WalletMoney $debits): WalletMoney
    {
        return $credits->minus($debits);
    }

    public function total(): WalletMoney
    {
        return $this->available->plus($this->held)->plus($this->committed);
    }
}
