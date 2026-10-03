<?php

declare(strict_types=1);

namespace App\Infrastructure\Primary;

use App\Application\Primary\Contracts\PrimaryPurchaseIndex;
use App\Domain\Operations\CommandRejection;
use App\Models\PrimaryCommitment;
use App\Models\PrimaryReservationRecord;

/** Reads only immutable identifiers; takes no locks and reveals nothing a 404 would not. */
final class EloquentPrimaryPurchaseIndex implements PrimaryPurchaseIndex
{
    public function campaignOfReservation(string $reservationId): string
    {
        $campaignId = PrimaryReservationRecord::query()->whereKey($reservationId)->value('business_campaign_id');

        return is_string($campaignId) ? $campaignId : throw new CommandRejection('RESERVATION_NOT_FOUND', 404);
    }

    public function commitment(string $commitmentId): array
    {
        $reservationId = PrimaryCommitment::query()->whereKey($commitmentId)->value('primary_reservation_id');
        if (! is_string($reservationId)) {
            throw new CommandRejection('NOT_FOUND', 404);
        }

        return ['campaign_id' => $this->campaignOfReservation($reservationId), 'reservation_id' => $reservationId];
    }
}
