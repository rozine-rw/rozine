<?php

declare(strict_types=1);

namespace App\Domain\Primary;

use DateTimeImmutable;

/**
 * A transition decision, not a ledger write or idempotency receipt. The store
 * must persist it with the corresponding cash/exposure move in one transaction.
 */
final readonly class PrimaryReservation
{
    private function __construct(public UnitRights $rights, public PrimaryTerms $terms,
        public ReservationWindow $window, public string $state) {}

    public static function hold(UnitRights $rights, PrimaryTerms $terms, ReservationWindow $window): self
    {
        $terms->requireRights($rights);

        return new self($rights, $terms, $window, 'held');
    }

    /** The operation journal handles original successful replays before this transition. */
    public function confirm(DateTimeImmutable $at, PrimaryTerms $currentTerms, string $acknowledgedVersion, string $acknowledgedSha256): self
    {
        if ($this->state !== 'held') {
            throw new PrimaryViolation('RESERVATION_NOT_HELD');
        }
        $this->window->requireOpen($at);
        $this->terms->requireAcknowledged($currentTerms, $acknowledgedVersion, $acknowledgedSha256);

        return new self($this->rights, $this->terms, $this->window, 'confirmed');
    }

    /** A fresh fee disclosure keeps the original rights and never extends the hold. */
    public function requote(DateTimeImmutable $at, PrimaryTerms $terms): self
    {
        if ($this->state !== 'held') {
            throw new PrimaryViolation('RESERVATION_NOT_HELD');
        }
        $this->window->requireOpen($at);
        if ($terms->ratePercent !== $this->terms->ratePercent || $terms->termMonths !== $this->terms->termMonths) {
            throw new PrimaryViolation('DISCLOSURE_STALE');
        }
        $terms->requireRights($this->rights);

        return new self($this->rights, $terms, $this->window, 'held');
    }

    /** Terminal release is a no-op; a confirmed commitment uses its own refund path. */
    public function release(DateTimeImmutable $at): self
    {
        if ($this->state === 'confirmed') {
            throw new PrimaryViolation('RESERVATION_NOT_HELD');
        }
        if ($this->state !== 'held') {
            return $this;
        }
        if (! $this->window->isExpired($at)) {
            $this->window->requireOpen($at);
        }

        return new self($this->rights, $this->terms, $this->window, $this->window->isExpired($at) ? 'expired' : 'released');
    }
}
