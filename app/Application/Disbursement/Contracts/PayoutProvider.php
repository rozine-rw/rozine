<?php

declare(strict_types=1);

namespace App\Application\Disbursement\Contracts;

use App\Application\Disbursement\PayoutInstruction;
use App\Application\Disbursement\VerifiedPayoutEvent;

/**
 * The payout provider port (MC-08, §10.8). It is called only outside every transaction, after
 * the intent and its outbox row committed. An acknowledgement is not a payment: only a verified,
 * matching, reconciled observation moves anything. `query` observes the same durable operation
 * and never sends.
 */
interface PayoutProvider
{
    public function name(): string;

    /** Whether resending the same instruction is safe, so an interrupted claim may be resent. */
    public function idempotentSends(): bool;

    /** Asks the provider to pay; true when it acknowledged the instruction. */
    public function send(PayoutInstruction $instruction): bool;

    /** Asks about the same operation; null when the provider gave no authenticated answer. */
    public function query(PayoutInstruction $instruction): ?VerifiedPayoutEvent;

    /**
     * Authenticates a provider message. An unauthenticated message is refused, never recorded.
     *
     * @param  array<string, mixed>  $message
     */
    public function verify(array $message): VerifiedPayoutEvent;
}
