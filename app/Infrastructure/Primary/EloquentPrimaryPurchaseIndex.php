<?php

declare(strict_types=1);

namespace App\Infrastructure\Primary;

use App\Application\Business\Contracts\PrimaryCampaignSource;
use App\Application\Primary\Contracts\PrimaryPurchaseIndex;
use App\Domain\Operations\CommandRejection;
use App\Models\PrimaryCommitment;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use RuntimeException;

/** Reads only immutable identifiers and initial terms; takes no locks and reveals nothing a 404 would not. */
final readonly class EloquentPrimaryPurchaseIndex implements PrimaryPurchaseIndex
{
    public function __construct(private PrimaryCampaignSource $campaigns) {}

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

    public function walletLabels(string $partyId, array $reservationIds): array
    {
        $records = PrimaryReservationRecord::query()->where('party_id', $partyId)->whereKey($reservationIds)->get();
        $initial = PrimaryReservationVersion::query()->whereIn('primary_reservation_id', $records->modelKeys())->where('revision', 1)
            ->get()->keyBy('primary_reservation_id');
        $titles = [];
        $labels = [];
        foreach ($records as $record) {
            $policy = $initial->get($record->id)?->payload['terms']['policy_version'] ?? null;
            $policy = is_string($policy) ? $policy : throw new RuntimeException('RESERVATION_INTEGRITY_FAILED');
            $titles[$record->business_campaign_id] ??= $this->campaigns->presentation($record->business_campaign_id)['title'];
            $labels[$record->id] = ['campaign_id' => $record->business_campaign_id, 'deal_name' => $titles[$record->business_campaign_id], 'units' => (string) $record->units, 'policy_version' => $policy];
        }

        return $labels;
    }

    public function activeHolds(string $partyId): array
    {
        $records = PrimaryReservationRecord::query()->where('party_id', $partyId)->where('expires_at', '>', now())
            ->whereRaw("(SELECT v.state FROM primary_reservation_versions v WHERE v.primary_reservation_id = primary_reservations.id
                ORDER BY v.revision DESC LIMIT 1) = 'held'")->orderByDesc('created_at')->orderByDesc('id')->get();
        $titles = [];

        return array_values($records->map(function (PrimaryReservationRecord $record) use (&$titles): array {
            $titles[$record->business_campaign_id] ??= $this->campaigns->presentation($record->business_campaign_id)['title'];

            return ['reservation_id' => $record->id, 'campaign_id' => $record->business_campaign_id, 'deal_name' => $titles[$record->business_campaign_id],
                'amount' => ['currency' => 'RWF', 'amount' => (string) $record->principal], 'units' => (string) $record->units,
                'starts_at' => $record->created_at->utc()->toIso8601String(), 'expires_at' => $record->expires_at->utc()->toIso8601String()];
        })->all());
    }
}
