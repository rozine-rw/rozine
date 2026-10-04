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
     * Investor's available balance and any fee in fee revenue. A zero fee posts no fee line, and
     * a fee that would leave nothing (or less than nothing) to credit is refused outright.
     */
    public static function depositCredit(WalletMoney $gross, WalletMoney $fee): self
    {
        if ($gross->compareTo($fee) <= 0) {
            throw new WalletViolation('DEPOSIT_NET_NOT_POSITIVE');
        }
        $lines = [new JournalLine('deposit_clearing', 'debit', $gross), new JournalLine('investor_available', 'credit', $gross->minus($fee))];
        if (! $fee->isZero()) {
            $lines[] = new JournalLine('deposit_fee_revenue', 'credit', $fee);
        }

        return new self('deposit_credit', $lines);
    }

    /**
     * A credited Business deposit: the gross leaves deposit clearing, the net lands in the Business's
     * available balance and any fee in fee revenue, under the same fee rules as an Investor deposit.
     */
    public static function businessDepositCredit(WalletMoney $gross, WalletMoney $fee): self
    {
        if ($gross->compareTo($fee) <= 0) {
            throw new WalletViolation('DEPOSIT_NET_NOT_POSITIVE');
        }
        $lines = [new JournalLine('deposit_clearing', 'debit', $gross), new JournalLine('business_available', 'credit', $gross->minus($fee))];
        if (! $fee->isZero()) {
            $lines[] = new JournalLine('deposit_fee_revenue', 'credit', $fee);
        }

        return new self('business_deposit_credit', $lines);
    }

    /**
     * A primary movement: the source bucket is debited and the destination credited by the same
     * amount. Between the Investor's own buckets (hold, commit, release, refund) the total never
     * changes; an issue moves committed principal out to the system settlement account, so it
     * lowers the Investor's total by exactly that amount.
     */
    public static function primary(string $kind, WalletMoney $amount): self
    {
        [$from, $to] = PrimaryPosting::MOVEMENTS[$kind] ?? throw new WalletViolation('WALLET_POSTING_KIND_INVALID');

        return new self($kind, [new JournalLine($from, 'debit', $amount), new JournalLine($to, 'credit', $amount)]);
    }
}
