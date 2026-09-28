<?php

declare(strict_types=1);

namespace App\Domain\Wallet;

/**
 * The provider outcome transitions (MC-08). Only a success on a non-final intent is applied as a
 * credit, once. `pending` and `unknown` never become final on their own. A failure after a success
 * opens reconciliation and never reverses it; a success after a failure is a conflict, never a
 * credit. A repeat of the current state changes nothing.
 */
final readonly class DepositOutcome
{
    public const array STATES = ['pending', 'unknown', 'succeeded', 'failed'];

    /** @param 'applied'|'duplicate'|'after_final'|'conflict' $disposition */
    private function __construct(public string $disposition, public string $state) {}

    public static function transition(string $current, string $event): self
    {
        if (! in_array($current, self::STATES, true) || ! in_array($event, self::STATES, true)) {
            throw new WalletViolation('DEPOSIT_OUTCOME_STATE_INVALID');
        }
        if ($current === $event) {
            return new self('duplicate', $current);
        }
        if (! self::isFinal($current)) {
            return new self('applied', $event);
        }
        if ($current === 'failed' && $event === 'succeeded') {
            return new self('conflict', $current);
        }

        return new self('after_final', $current);
    }

    public static function isFinal(string $state): bool
    {
        return $state === 'succeeded' || $state === 'failed';
    }

    /** Whether this transition posts the credit. */
    public function credits(): bool
    {
        return $this->disposition === 'applied' && $this->state === 'succeeded';
    }
}
