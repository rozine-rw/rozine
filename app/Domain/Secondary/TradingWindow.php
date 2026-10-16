<?php

declare(strict_types=1);

namespace App\Domain\Secondary;

use Brick\Math\BigInteger;
use Brick\Math\RoundingMode;
use DateTimeImmutable;
use DateTimeZone;

/**
 * Whether one note may trade now, from facts read once under the caller's locks (SEC-R01, R07,
 * R08, INV-R01). Only a current note trades; a market or note halt stops everything; and from
 * 23:00 Kigali on a due date until that instalment's payouts post, the note pauses so each payout
 * goes to whoever holds the units when it is collected. A buyer stays within half of the raise,
 * counting primary and secondary units together, and never buys their own listing.
 */
final readonly class TradingWindow
{
    public const array HEALTH = ['current', 'late', 'defaulted', 'disputed'];

    /** @var 'current'|'late'|'defaulted'|'disputed' */
    public string $noteHealth;

    /**
     * @param  list<string>  $dueDates  the note's schedule, `Y-m-d` in Kigali
     * @param  string|null  $postedThrough  the last due date whose payouts have posted
     */
    public function __construct(
        string $noteHealth,
        public bool $marketHalted,
        public bool $noteHalted,
        public array $dueDates,
        public ?string $postedThrough,
    ) {
        $this->noteHealth = match ($noteHealth) {
            'current', 'late', 'defaulted', 'disputed' => $noteHealth,
            default => throw new SecondaryViolation('INVALID_NOTE_HEALTH'),
        };
    }

    /** The instant the current due-date pause began, or null when the note is not paused. */
    public function pausedSince(DateTimeImmutable $at): ?DateTimeImmutable
    {
        $zone = new DateTimeZone(SecondaryTerms::ZONE);
        foreach ($this->dueDates as $dueOn) {
            if ($this->postedThrough !== null && $dueOn <= $this->postedThrough) {
                continue;
            }
            $pause = new DateTimeImmutable($dueOn.' '.SecondaryTerms::PAUSE_FROM, $zone);
            if ($at >= $pause) {
                return $pause;
            }
        }

        return null;
    }

    /** Listing, repricing, reserving and settling all need an open window. */
    public function requireOpen(DateTimeImmutable $at): void
    {
        $refusal = match (true) {
            $this->noteHealth !== 'current' => 'NOTE_NOT_TRADABLE',
            $this->marketHalted => 'MARKET_HALTED',
            $this->noteHalted => 'NOTE_HALTED',
            $this->pausedSince($at) !== null => 'TRADING_PAUSED',
            default => null,
        };
        if ($refusal !== null) {
            throw new SecondaryViolation($refusal);
        }
    }

    /**
     * The most units this buyer may still take in the note: half the raise less what they
     * already hold or have reserved, primary and secondary together.
     */
    public static function buyerRoom(string $raiseUnits, string $heldUnits): BigInteger
    {
        $limit = BigInteger::of($raiseUnits)->dividedBy(2, RoundingMode::Down);
        $held = BigInteger::of($heldUnits);

        return $held->isGreaterThanOrEqualTo($limit) ? BigInteger::zero() : $limit->minus($held);
    }

    public static function requireBuyer(string $sellerPartyId, string $buyerPartyId, string $raiseUnits, string $heldUnits, string $buying): void
    {
        if ($sellerPartyId === $buyerPartyId) {
            throw new SecondaryViolation('OWN_LISTING');
        }
        if (Quantity::units($buying)->isGreaterThan(self::buyerRoom($raiseUnits, $heldUnits))) {
            throw new SecondaryViolation('HOLDING_LIMIT_EXCEEDED');
        }
    }
}
