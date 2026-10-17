<?php

declare(strict_types=1);

namespace App\Application\Wallet\Contracts;

use App\Application\Wallet\VerifiedWithdrawalEvent;
use App\Application\Wallet\WithdrawalInstruction;

/**
 * The withdrawal provider port (LED-R04). It is called only after the withdrawal, its hold and its
 * outbox row have committed, outside every database lock. An acknowledgement is not a payment: only
 * a verified event pays the withdrawal out or returns it.
 */
interface WithdrawalProvider
{
    public function name(): string;

    /** The provider's own charge on a payout, in whole RWF, shown before the Investor confirms. */
    public function charge(): string;

    /** Whether resending the same instruction is safe, so an interrupted dispatch may be resent. */
    public function idempotentSends(): bool;

    /** Asks the provider to pay out; true when it acknowledged the instruction. */
    public function send(WithdrawalInstruction $instruction): bool;

    /**
     * Authenticates a provider message. An unauthenticated message is refused, never recorded.
     *
     * @param  array<string, mixed>  $message
     */
    public function verify(array $message): VerifiedWithdrawalEvent;
}
