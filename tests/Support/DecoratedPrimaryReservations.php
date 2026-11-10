<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Application\Primary\Contracts\PrimaryReservations;
use App\Application\Primary\PrimaryCampaignReturns;
use App\Application\Primary\PrimaryFundingCandidate;
use App\Application\Primary\ReservationConfirmation;
use App\Application\Primary\ReservationRefund;
use App\Application\Primary\ReservationRelease;
use App\Application\Primary\ReservedCheckout;
use Closure;

/**
 * The live Primary reservations with a hook on what a whole-cart checkout asks of each child, so a
 * test can make one child answer other than it was admitted and prove the cart rolls back whole.
 */
final readonly class DecoratedPrimaryReservations implements PrimaryReservations
{
    public function __construct(private PrimaryReservations $live, private ?Closure $requotes = null, private ?Closure $confirmed = null,
        private ?Closure $releasing = null, private ?Closure $expiring = null) {}

    public function reservationFacts(string $campaignId, string $partyId, string $reservationId): array
    {
        return $this->live->reservationFacts($campaignId, $partyId, $reservationId);
    }

    public function commitmentFacts(string $campaignId, string $partyId, string $commitmentId): array
    {
        return $this->live->commitmentFacts($campaignId, $partyId, $commitmentId);
    }

    public function reserve(string $campaignId, string $partyId, string $originOperationId, string $units, Closure $admit): ReservedCheckout
    {
        return $this->live->reserve($campaignId, $partyId, $originOperationId, $units, $admit);
    }

    public function confirm(string $campaignId, string $reservationId, string $partyId, string $operationId, int $expectedRevision,
        string $disclosureVersion, string $disclosureSha256, Closure $admit): ReservationConfirmation
    {
        $result = $this->live->confirm($campaignId, $reservationId, $partyId, $operationId, $expectedRevision, $disclosureVersion, $disclosureSha256, $admit);

        return $this->confirmed === null ? $result : ($this->confirmed)($result);
    }

    public function requotes(string $campaignId, string $reservationId, string $partyId, Closure $admit): bool
    {
        $requotes = $this->live->requotes($campaignId, $reservationId, $partyId, $admit);

        return $this->requotes === null ? $requotes : ($this->requotes)($requotes);
    }

    public function release(string $campaignId, string $reservationId, string $partyId, string $operationId, int $expectedRevision): ReservationRelease
    {
        if ($this->releasing !== null) {
            ($this->releasing)($campaignId);
        }

        return $this->live->release($campaignId, $reservationId, $partyId, $operationId, $expectedRevision);
    }

    public function lockFundingCandidate(string $campaignId): PrimaryFundingCandidate
    {
        return $this->live->lockFundingCandidate($campaignId);
    }

    public function lockReturnedCampaign(string $campaignId): PrimaryCampaignReturns
    {
        return $this->live->lockReturnedCampaign($campaignId);
    }

    public function settleExpiredCampaign(string $campaignId, string $closureId): array
    {
        return $this->live->settleExpiredCampaign($campaignId, $closureId);
    }

    public function refund(string $campaignId, string $reservationId, string $partyId, int $expectedRevision): ReservationRefund
    {
        return $this->live->refund($campaignId, $reservationId, $partyId, $expectedRevision);
    }

    public function withdraw(string $campaignId, string $reservationId, string $partyId, int $expectedRevision): ReservationRefund
    {
        return $this->live->withdraw($campaignId, $reservationId, $partyId, $expectedRevision);
    }

    public function expireDue(int $limit): int
    {
        return $this->live->expireDue($limit);
    }

    public function expire(string $campaignId, string $reservationId, ?string $operationId = null): ?ReservationRelease
    {
        if ($this->expiring !== null) {
            return ($this->expiring)($campaignId, fn (): ?ReservationRelease => $this->live->expire($campaignId, $reservationId, $operationId));
        }

        return $this->live->expire($campaignId, $reservationId, $operationId);
    }
}
