<?php

declare(strict_types=1);

namespace App\Domain\Wallet;

/** One positive posting on a named account kind. */
final readonly class JournalLine
{
    public const array ACCOUNTS = ['investor_available', 'investor_held', 'investor_committed', 'business_available', 'deposit_clearing', 'deposit_fee_revenue', 'disbursement_settlement'];

    /** Direction is 'debit' or 'credit'; anything else is refused. */
    public function __construct(public string $account, public string $direction, public WalletMoney $amount)
    {
        if (! in_array($account, self::ACCOUNTS, true) || ! in_array($direction, ['debit', 'credit'], true) || $amount->isZero()) {
            throw new WalletViolation('JOURNAL_LINE_INVALID');
        }
    }
}
