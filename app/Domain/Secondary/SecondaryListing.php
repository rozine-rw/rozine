<?php

declare(strict_types=1);

namespace App\Domain\Secondary;

use Brick\Math\BigInteger;
use DateInterval;
use DateTimeImmutable;
use DateTimeZone;

/**
 * One seller's listing of units from one Holding lot (SEC-R02 to R09), as an immutable state.
 * A listing is `open` for seven days, then `expired` at no cost. A buyer may reserve any whole
 * number of its units no other buyer holds, for five minutes (SEC-R04, SEC-R05): several buyers may
 * each hold part of one listing at once, one reservation each. While any reservation is open the
 * seller can neither cancel nor reprice, and an expired reservation simply lapses. Settling a
 * reservation takes its units off the listing, which is `sold` once none remain. The seller may
 * cancel when nothing is reserved; staff may cancel with a recorded reason. A halt releases every
 * reservation and cancels the listing for good, so nothing reappears when the halt lifts. Every
 * transition that would change a closed listing is refused.
 */
final readonly class SecondaryListing
{
    public const array STATES = ['open', 'sold', 'cancelled', 'expired'];

    /**
     * @param  'open'|'sold'|'cancelled'|'expired'  $state
     * @param  list<array{id: string, buyer_party_id: string, units: string, expires_at: DateTimeImmutable}>  $reservations
     * @param  array{by: 'seller'|'staff'|'halt', reason: string|null}|null  $cancellation
     */
    private function __construct(
        public string $id,
        public string $sellerPartyId,
        public string $holdingId,
        public string $listedUnits,
        public string $remainingUnits,
        public string $price,
        public DateTimeImmutable $listedAt,
        public DateTimeImmutable $expiresAt,
        public string $state,
        public array $reservations,
        public ?array $cancellation,
    ) {}

    /**
     * Lists units the seller holds free of any other listing, at a whole-franc price within the
     * cap, in an open trading window.
     */
    public static function open(string $id, string $sellerPartyId, string $holdingId, string $units, string $freeUnits, string $price,
        BigInteger $cap, TradingWindow $window, DateTimeImmutable $at): self
    {
        $window->requireOpen($at);
        if (Quantity::units($units)->isGreaterThan(BigInteger::of($freeUnits))) {
            throw new SecondaryViolation('UNITS_NOT_HELD');
        }
        PriceCap::requireWithin($price, $cap);
        $listedAt = $at->setTimezone(new DateTimeZone('UTC'));

        return new self($id, $sellerPartyId, $holdingId, $units, $units, $price, $listedAt,
            $listedAt->add(new DateInterval('PT'.SecondaryTerms::LISTING_SECONDS.'S')), 'open', [], null);
    }

    /**
     * A listing as it was recorded, for the adapter that keeps it. Every rule applies again from here:
     * the restored state goes through the same transitions as one just opened.
     *
     * @param  'open'|'sold'|'cancelled'|'expired'  $state
     * @param  list<array{id: string, buyer_party_id: string, units: string, expires_at: DateTimeImmutable}>  $reservations
     * @param  array{by: 'seller'|'staff'|'halt', reason: string|null}|null  $cancellation
     */
    public static function restore(string $id, string $sellerPartyId, string $holdingId, string $listedUnits, string $remainingUnits, string $price,
        DateTimeImmutable $listedAt, DateTimeImmutable $expiresAt, string $state, array $reservations, ?array $cancellation): self
    {
        return new self($id, $sellerPartyId, $holdingId, $listedUnits, $remainingUnits, $price, $listedAt, $expiresAt, $state, $reservations, $cancellation);
    }

    /** The state as of an instant: lapsed reservations are gone, and a listing past its seven days with none left is expired. */
    public function at(DateTimeImmutable $at): self
    {
        if ($this->state !== 'open') {
            return $this;
        }
        $reservations = array_values(array_filter($this->reservations, fn (array $reservation): bool => $at < $reservation['expires_at']));
        $state = $reservations === [] && $at >= $this->expiresAt ? 'expired' : 'open';

        return $this->with(state: $state, reservations: $reservations);
    }

    /**
     * The open reservation with this id, if there is one.
     *
     * @return array{id: string, buyer_party_id: string, units: string, expires_at: DateTimeImmutable}|null
     */
    public function reservation(string $reservationId): ?array
    {
        foreach ($this->reservations as $reservation) {
            if ($reservation['id'] === $reservationId) {
                return $reservation;
            }
        }

        return null;
    }

    /** Units no buyer holds: what is left to reserve. */
    public function unreservedUnits(): string
    {
        $held = array_reduce($this->reservations, fn (BigInteger $sum, array $reservation): BigInteger => $sum->plus($reservation['units']), BigInteger::zero());

        return (string) BigInteger::of($this->remainingUnits)->minus($held);
    }

    /**
     * Reserves units for a buyer at the listed price, which must still be within the note's current
     * cap: a payout lowers the cap, so a price set before it may no longer trade (SEC-R03).
     */
    public function reserve(string $reservationId, string $buyerPartyId, string $units, string $raiseUnits, string $heldUnits,
        BigInteger $cap, TradingWindow $window, DateTimeImmutable $at, int $capPercent = 10): self
    {
        $current = $this->requireOpen($at);
        $window->requireOpen($at);
        PriceCap::requireWithin($current->price, $cap);
        $free = BigInteger::of($current->unreservedUnits());
        $buyerHolds = array_filter($current->reservations, fn (array $reservation): bool => $reservation['buyer_party_id'] === $buyerPartyId);
        if ($free->isZero() || $buyerHolds !== []) {
            throw new SecondaryViolation('LISTING_RESERVED');
        }
        TradingWindow::requireBuyer($current->sellerPartyId, $buyerPartyId, $raiseUnits, $heldUnits, $units, $capPercent);
        if (Quantity::units($units)->isGreaterThan($free)) {
            throw new SecondaryViolation('UNITS_NOT_LISTED');
        }
        $expiresAt = $at->setTimezone(new DateTimeZone('UTC'))->add(new DateInterval('PT'.SecondaryTerms::RESERVATION_SECONDS.'S'));

        return $current->with(reservations: [...$current->reservations,
            ['id' => $reservationId, 'buyer_party_id' => $buyerPartyId, 'units' => $units, 'expires_at' => $expiresAt]]);
    }

    /**
     * Settles one open reservation in one step, returning the listing that remains and what the
     * settlement moves. A lapsed reservation can no longer settle, and nor can one whose price a
     * payout has since put above the note's current cap.
     *
     * @return array{listing: self, quote: SettlementQuote}
     */
    public function settle(string $reservationId, BigInteger $cap, TradingWindow $window, DateTimeImmutable $at): array
    {
        $current = $this->at($at);
        $reservation = $current->reservation($reservationId) ?? throw new SecondaryViolation('RESERVATION_NOT_OPEN');
        $window->requireOpen($at);
        PriceCap::requireWithin($current->price, $cap);
        $quote = SettlementQuote::of($reservation['units'], $current->price);
        $remaining = BigInteger::of($current->remainingUnits)->minus($quote->units);
        $others = array_values(array_filter($current->reservations, fn (array $open): bool => $open['id'] !== $reservationId));

        return ['listing' => $current->with(remainingUnits: (string) $remaining, state: $remaining->isZero() ? 'sold' : 'open', reservations: $others),
            'quote' => $quote];
    }

    public function reprice(string $price, BigInteger $cap, TradingWindow $window, DateTimeImmutable $at): self
    {
        $current = $this->requireUnreserved($at);
        $window->requireOpen($at);
        PriceCap::requireWithin($price, $cap);

        return $current->with(price: $price);
    }

    /** The seller's own cancellation, refused while a buyer holds a reservation. */
    public function cancel(DateTimeImmutable $at): self
    {
        return $this->requireUnreserved($at)->with(state: 'cancelled', cancellation: ['by' => 'seller', 'reason' => null]);
    }

    /** A staff cancellation, which needs a recorded reason shown to the seller. */
    public function cancelByStaff(string $reason, DateTimeImmutable $at): self
    {
        if (trim($reason) === '') {
            throw new SecondaryViolation('REASON_REQUIRED');
        }

        return $this->requireUnreserved($at)->with(state: 'cancelled', cancellation: ['by' => 'staff', 'reason' => $reason]);
    }

    /** A market or note halt: every reservation is released and the listing is cancelled for good. */
    public function halt(string $reason, DateTimeImmutable $at): self
    {
        if (trim($reason) === '') {
            throw new SecondaryViolation('REASON_REQUIRED');
        }

        return $this->requireOpen($at)->with(state: 'cancelled', reservations: [], cancellation: ['by' => 'halt', 'reason' => $reason]);
    }

    private function requireOpen(DateTimeImmutable $at): self
    {
        $current = $this->at($at);
        if ($current->state !== 'open') {
            throw new SecondaryViolation('LISTING_CLOSED');
        }

        return $current;
    }

    private function requireUnreserved(DateTimeImmutable $at): self
    {
        $current = $this->requireOpen($at);
        if ($current->reservations !== []) {
            throw new SecondaryViolation('LISTING_RESERVED');
        }

        return $current;
    }

    /**
     * @param  'open'|'sold'|'cancelled'|'expired'|null  $state
     * @param  list<array{id: string, buyer_party_id: string, units: string, expires_at: DateTimeImmutable}>|null  $reservations
     * @param  array{by: 'seller'|'staff'|'halt', reason: string|null}|null  $cancellation
     */
    private function with(?string $remainingUnits = null, ?string $price = null, ?string $state = null, ?array $reservations = null,
        ?array $cancellation = null): self
    {
        return new self($this->id, $this->sellerPartyId, $this->holdingId, $this->listedUnits, $remainingUnits ?? $this->remainingUnits,
            $price ?? $this->price, $this->listedAt, $this->expiresAt, $state ?? $this->state,
            $reservations ?? $this->reservations, $cancellation ?? $this->cancellation);
    }
}
