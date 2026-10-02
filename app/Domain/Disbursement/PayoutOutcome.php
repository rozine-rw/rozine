<?php

declare(strict_types=1);

namespace App\Domain\Disbursement;

/**
 * How one authenticated provider observation of a payout is recorded (MC-08, §10.8). Nothing here
 * moves money: only the reconciliation transaction issues or refunds.
 *
 * - The same event identity with the same content is a harmless duplicate; with different content
 *   it is a `key_conflict`, never the original.
 * - An authenticated observation that does not match the intent (operation, environment, currency,
 *   exact amount, destination) is `unverifiable`: retained, and it blocks.
 * - From `pending` or `unknown` a new state is `applied`; a repeat of the current state is a
 *   `duplicate`.
 * - A non-final observation after a final one is `stale`: a lagging query or an out-of-order
 *   webhook. It is retained as evidence but can never downgrade a verified final, so it blocks
 *   nothing.
 * - A failure after a success is `after_final`: it opens an exception and never reverses anything.
 * - A success after a failure is a `conflict` that blocks any refund.
 */
final readonly class PayoutOutcome
{
    public const array STATES = ['pending', 'unknown', 'succeeded', 'failed'];

    public const array DISPOSITIONS = ['applied', 'duplicate', 'stale', 'after_final', 'conflict', 'key_conflict', 'unverifiable'];

    /** @param 'applied'|'duplicate'|'stale'|'after_final'|'conflict'|'key_conflict'|'unverifiable' $disposition */
    private function __construct(public string $disposition, public string $state) {}

    public static function transition(string $current, string $event): self
    {
        if (! in_array($current, self::STATES, true) || ! in_array($event, self::STATES, true)) {
            throw new DisbursementViolation('PAYOUT_OUTCOME_STATE_INVALID');
        }
        if ($current === $event) {
            return new self('duplicate', $current);
        }
        if (! self::isFinal($current)) {
            return new self('applied', $event);
        }
        if (! self::isFinal($event)) {
            return new self('stale', $current);
        }
        if ($current === 'failed') {
            return new self('conflict', $current);
        }

        return new self('after_final', $current);
    }

    /**
     * Classifies an authenticated observation against what is already recorded.
     *
     * @param  string|null  $recordedContentSha256  the content hash of the observation that claims this event identity, if any
     * @param  list<string>  $mismatches  the intent facts this observation contradicts (see Reconciliation::mismatches)
     */
    public static function observe(string $current, string $event, ?string $recordedContentSha256, string $contentSha256, array $mismatches): self
    {
        if (! in_array($current, self::STATES, true) || ! in_array($event, self::STATES, true)) {
            throw new DisbursementViolation('PAYOUT_OUTCOME_STATE_INVALID');
        }
        // Evidence that does not match this intent is refused first: it never claims, or conflicts
        // with, an identity that belongs to another intent.
        if ($mismatches !== []) {
            return new self('unverifiable', $current);
        }
        if ($recordedContentSha256 !== null) {
            return hash_equals($recordedContentSha256, $contentSha256) ? new self('duplicate', $current) : new self('key_conflict', $current);
        }

        return self::transition($current, $event);
    }

    public static function isFinal(string $state): bool
    {
        return $state === 'succeeded' || $state === 'failed';
    }

    /** Whether this observation opens an exception that stays blocked until an authorized resolution. */
    public function blocks(): bool
    {
        return in_array($this->disposition, ['after_final', 'conflict', 'key_conflict', 'unverifiable'], true);
    }
}
