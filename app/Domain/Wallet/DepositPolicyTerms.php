<?php

declare(strict_types=1);

namespace App\Domain\Wallet;

/**
 * An applicable, versioned deposit policy. Its fee is explicit; a missing policy is not this object
 * with a zero fee, it is no policy at all (`POLICY_INPUT_REQUIRED`).
 */
final readonly class DepositPolicyTerms
{
    public function __construct(
        public string $version,
        public bool $synthetic,
        public WalletMoney $fee,
        public ?WalletMoney $minimum,
        public ?WalletMoney $maximum,
    ) {}

    /** Why the amount cannot be deposited under this policy, or null when it can. */
    public function boundsError(WalletMoney $amount): ?string
    {
        if ($this->minimum !== null && $amount->compareTo($this->minimum) < 0) {
            return 'Deposit at least RWF '.$this->minimum->amount().'.';
        }
        if ($this->maximum !== null && $amount->compareTo($this->maximum) > 0) {
            return 'Deposit at most RWF '.$this->maximum->amount().'.';
        }
        if ($amount->compareTo($this->fee) <= 0) {
            return 'Deposit more than the RWF '.$this->fee->amount().' fee.';
        }

        return null;
    }

    /** What lands in the available balance on a verified success: the amount less the fee. */
    public function credited(WalletMoney $amount): WalletMoney
    {
        return $amount->minus($this->fee);
    }
}
