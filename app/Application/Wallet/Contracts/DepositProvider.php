<?php

declare(strict_types=1);

namespace App\Application\Wallet\Contracts;

use App\Application\Wallet\DepositInstruction;
use App\Application\Wallet\VerifiedDepositEvent;

/**
 * The deposit provider port (MC-08). It is called only after the intent and its outbox row have
 * committed, outside every database lock. An acknowledgement is not a success: only a verified
 * event can move money.
 */
interface DepositProvider
{
    public function name(): string;

    /** Whether resending the same instruction is safe, so an interrupted dispatch may be resent. */
    public function idempotentSends(): bool;

    /** Asks the provider to collect; true when it acknowledged the instruction. */
    public function initiate(DepositInstruction $instruction): bool;

    /**
     * Authenticates a provider message. An unauthenticated message is refused, never recorded.
     *
     * @param  array<string, mixed>  $message
     */
    public function verify(array $message): VerifiedDepositEvent;
}
