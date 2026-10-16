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
 * number of its remaining units for five minutes; while that reservation is open the seller can
 * neither cancel nor reprice, and an expired reservation simply lapses. Settling a reservation
 * takes its units off the listing, which is `sold` once none remain. The seller may cancel when
 * nothing is reserved; staff may cancel with a recorded reason. A halt releases any reservation
 * and cancels the listing for good, so nothing reappears when the halt lifts. Every transition
 * that would change a closed listing is refused.
 */
final readonly class SecondaryListing
{
    public const array STATES = ['open', 'sold', 'cancelled', 'expired'];

    /**
     * @param  'open'|'sold'|'cancelled'|'expired'  $state
     * @param  array{id: string, buyer_party_id: string, units: string, expires_at: DateTimeImmutable}|null  $reservation
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
        public ?array $reservation,
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
            $listedAt->add(new DateInterval('PT'.SecondaryTerms::LISTING_SECONDS.'S')), 'open', null, null);
    }

    /** The state as of an instant: a lapsed reservation is gone, and a listing past its seven days is expired. */
    public function at(DateTimeImmutable $at): self
    {
        if ($this->state !== 'open') {
            return $this;
        }
        $reservation = $this->reservation !== null && $at >= $this->reservation['expires_at'] ? null : $this->reservation;
        $state = $reservation === null && $at >= $this->expiresAt ? 'expired' : 'open';

        return $this->with(state: $state, reservation: $reservation);
    }

    public function reserve(string $reservationId, string $buyerPartyId, string $units, string $raiseUnits, string $heldUnits,
        TradingWindow $window, DateTimeImmutable $at): self
    {
        $current = $this->requireOpen($at);
        $window->requireOpen($at);
        if ($current->reservation !== null) {
            throw new SecondaryViolation('LISTING_RESERVED');
        }
        TradingWindow::requireBuyer($current->sellerPartyId, $buyerPartyId, $raiseUnits, $heldUnits, $units);
        if (Quantity::units($units)->isGreaterThan(BigInteger::of($current->remainingUnits))) {
            throw new SecondaryViolation('UNITS_NOT_LISTED');
        }
        $expiresAt = $at->setTimezone(new DateTimeZone('UTC'))->add(new DateInterval('PT'.SecondaryTerms::RESERVATION_SECONDS.'S'));

        return $current->with(reservation: ['id' => $reservationId, 'buyer_party_id' => $buyerPartyId, 'units' => $units, 'expires_at' => $expiresAt]);
    }

    /**
     * Settles the open reservation in one step, returning the listing that remains and what the
     * settlement moves. A lapsed reservation can no longer settle.
     *
     * @return array{listing: self, quote: SettlementQuote}
     */
    public function settle(string $reservationId, TradingWindow $window, DateTimeImmutable $at): array
    {
        $current = $this->at($at);
        if ($current->reservation === null || $current->reservation['id'] !== $reservationId) {
            throw new SecondaryViolation('RESERVATION_NOT_OPEN');
        }
        $window->requireOpen($at);
        $quote = SettlementQuote::of($current->reservation['units'], $current->price);
        $remaining = BigInteger::of($current->remainingUnits)->minus($quote->units);

        return ['listing' => $current->with(remainingUnits: (string) $remaining, state: $remaining->isZero() ? 'sold' : 'open', reservation: null),
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

    /** A market or note halt: any reservation is released and the listing is cancelled for good. */
    public function halt(string $reason, DateTimeImmutable $at): self
    {
        if (trim($reason) === '') {
            throw new SecondaryViolation('REASON_REQUIRED');
        }

        return $this->requireOpen($at)->with(state: 'cancelled', reservation: null, cancellation: ['by' => 'halt', 'reason' => $reason]);
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
        if ($current->reservation !== null) {
            throw new SecondaryViolation('LISTING_RESERVED');
        }

        return $current;
    }

    /**
     * @param  'open'|'sold'|'cancelled'|'expired'|null  $state
     * @param  array{id: string, buyer_party_id: string, units: string, expires_at: DateTimeImmutable}|null|false  $reservation
     * @param  array{by: 'seller'|'staff'|'halt', reason: string|null}|null  $cancellation
     */
    private function with(?string $remainingUnits = null, ?string $price = null, ?string $state = null, array|null|false $reservation = false,
        ?array $cancellation = null): self
    {
        return new self($this->id, $this->sellerPartyId, $this->holdingId, $this->listedUnits, $remainingUnits ?? $this->remainingUnits,
            $price ?? $this->price, $this->listedAt, $this->expiresAt, $state ?? $this->state,
            $reservation === false ? $this->reservation : $reservation, $cancellation ?? $this->cancellation);
    }
}
