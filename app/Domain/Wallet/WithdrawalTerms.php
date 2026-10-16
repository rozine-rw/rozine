<?php

declare(strict_types=1);

namespace App\Domain\Wallet;

/**
 * The withdrawal rule (BRS v2.0 LED-R04): only to the Investor's own verified account, RWF 1,000 to
 * 2,000,000 per withdrawal, and no Rozine fee. The provider's own charge is shown before the request
 * and comes out of what reaches the account. Holding unusual requests for review (OD-17) is not yet
 * decided, so nothing here holds one.
 */
final readonly class WithdrawalTerms
{
    public const string VERSION = 'LED-R04/2026-10';

    public const string MINIMUM = '1000';

    public const string MAXIMUM = '2000000';

    public function __construct(public WalletMoney $providerCharge) {}

    public function minimum(): WalletMoney
    {
        return WalletMoney::of(self::MINIMUM);
    }

    public function maximum(): WalletMoney
    {
        return WalletMoney::of(self::MAXIMUM);
    }

    /** Why the amount cannot be withdrawn from this available balance, or null when it can. */
    public function boundsError(WalletMoney $amount, WalletMoney $available): ?string
    {
        if ($amount->compareTo($this->minimum()) < 0) {
            return 'Withdraw at least RWF '.self::MINIMUM.'.';
        }
        if ($amount->compareTo($this->maximum()) > 0) {
            return 'Withdraw at most RWF '.self::MAXIMUM.'.';
        }
        if ($amount->compareTo($this->providerCharge) <= 0) {
            return 'Withdraw more than the RWF '.$this->providerCharge->amount().' provider charge.';
        }
        if ($amount->compareTo($available) > 0) {
            return 'Amount exceeds your available balance.';
        }

        return null;
    }

    /** What reaches the account: the amount less the provider's charge. */
    public function received(WalletMoney $amount): WalletMoney
    {
        return $amount->minus($this->providerCharge);
    }
}
