<?php

declare(strict_types=1);

namespace App\Domain\Wallet;

/**
 * A balanced set of postings. It cannot be built unbalanced, one-sided or empty; the database checks
 * the same rule again at commit.
 */
final readonly class JournalEntry
{
    /** @param list<JournalLine> $lines */
    private function __construct(public string $kind, public array $lines)
    {
        $debits = WalletMoney::zero();
        $credits = WalletMoney::zero();
        foreach ($lines as $line) {
            if ($line->direction === 'debit') {
                $debits = $debits->plus($line->amount);
            } else {
                $credits = $credits->plus($line->amount);
            }
        }
        if ($debits->isZero() || $debits->compareTo($credits) !== 0) {
            throw new WalletViolation('JOURNAL_ENTRY_UNBALANCED');
        }
    }

    /** @param list<JournalLine> $lines */
    public static function balanced(string $kind, array $lines): self
    {
        return new self($kind, $lines);
    }

    /**
     * A verified deposit success: the gross amount leaves provider clearing, the net lands in the
     * Investor's available balance and any fee in fee revenue. A zero fee posts no fee line.
     */
    public static function depositCredit(WalletMoney $gross, WalletMoney $fee): self
    {
        $lines = [new JournalLine('deposit_clearing', 'debit', $gross), new JournalLine('investor_available', 'credit', $gross->minus($fee))];
        if (! $fee->isZero()) {
            $lines[] = new JournalLine('deposit_fee_revenue', 'credit', $fee);
        }

        return new self('deposit_credit', $lines);
    }
}
