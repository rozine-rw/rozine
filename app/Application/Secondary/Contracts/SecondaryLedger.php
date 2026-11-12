<?php

declare(strict_types=1);

namespace App\Application\Secondary\Contracts;

use App\Application\Wallet\LockedWallet;
use Brick\Math\BigInteger;

/**
 * The resale postings (LED-R02, SEC-R06), each one balanced immutable entry in the caller's open
 * transaction on a wallet the caller has locked with `WalletPostings::lockForParty`. PostgreSQL binds
 * every one to its reservation, its amounts and its order again at commit. Each returns the entry id.
 */
interface SecondaryLedger
{
    /** The buyer's price leaves `investor_available` for `investor_held`; a short balance refuses `INSUFFICIENT_AVAILABLE_FUNDS` (422). */
    public function hold(LockedWallet $buyer, BigInteger $gross, string $reservationId, string $operationId): string;

    /** The held price goes back to `investor_available`. */
    public function release(LockedWallet $buyer, BigInteger $gross, string $reservationId, string $operationId): string;

    /** The held price moves to the platform `secondary_settlement` account. */
    public function purchase(LockedWallet $buyer, BigInteger $gross, string $reservationId, string $operationId): string;

    /** `secondary_settlement` pays the seller's `investor_available` less the fee, which goes to `secondary_fee_revenue`. */
    public function sale(LockedWallet $seller, BigInteger $gross, BigInteger $receives, BigInteger $fee, string $reservationId, string $operationId): string;
}
