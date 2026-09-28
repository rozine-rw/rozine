<?php

declare(strict_types=1);

namespace App\Infrastructure\Primary;

use App\Application\Primary\Contracts\CampaignReservationSummary;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use DateTimeImmutable;
use Illuminate\Database\Query\JoinClause;
use RuntimeException;

final class EloquentCampaignReservationSummary implements CampaignReservationSummary
{
    public function read(string $campaignId, DateTimeImmutable $at): array
    {
        $instant = $at->format('Y-m-d H:i:s.uP');
        $latest = PrimaryReservationVersion::query()
            ->whereIn('primary_reservation_id', PrimaryReservationRecord::query()->where('business_campaign_id', $campaignId)->select('id'))
            ->select('primary_reservation_id')
            ->selectRaw('MAX(revision) AS revision')->groupBy('primary_reservation_id');
        $totals = PrimaryReservationRecord::query()->from('primary_reservations as reservations')
            ->where('reservations.business_campaign_id', $campaignId)
            ->leftJoinSub($latest, 'latest', fn (JoinClause $join): JoinClause => $join->on('latest.primary_reservation_id', 'reservations.id'))
            ->leftJoin('primary_reservation_versions as versions', fn (JoinClause $join): JoinClause => $join
                ->on('versions.primary_reservation_id', 'reservations.id')->on('versions.revision', 'latest.revision'))
            ->leftJoin('primary_commitments as commitments', 'commitments.primary_reservation_id', 'reservations.id')
            ->selectRaw("COALESCE(SUM(reservations.principal) FILTER (WHERE versions.state = 'confirmed'), 0)::text AS committed_principal,
                COALESCE(SUM(reservations.units) FILTER (WHERE versions.state = 'confirmed'), 0)::text AS committed_units,
                COUNT(DISTINCT reservations.party_id) FILTER (WHERE versions.state = 'confirmed') AS investors,
                COALESCE(SUM(reservations.principal) FILTER (WHERE versions.state = 'held' AND reservations.expires_at > ?), 0)::text AS held_principal,
                COALESCE(SUM(reservations.units) FILTER (WHERE versions.state = 'held' AND reservations.expires_at > ?), 0)::text AS held_units,
                COALESCE(SUM(reservations.principal) FILTER (WHERE versions.state = 'held' AND reservations.expires_at <= ?), 0)::text AS expired_hold_principal,
                COALESCE(SUM(reservations.units) FILTER (WHERE versions.state = 'held' AND reservations.expires_at <= ?), 0)::text AS expired_hold_units,
                COALESCE(SUM(reservations.principal) FILTER (WHERE versions.state IN ('released', 'expired')), 0)::text AS returned_principal,
                COALESCE(SUM(reservations.units) FILTER (WHERE versions.state IN ('released', 'expired')), 0)::text AS returned_units,
                COALESCE(SUM(reservations.units), 0)::text AS occupied_units,
                COUNT(*) FILTER (WHERE versions.state IS NULL OR (versions.state = 'confirmed') <> (commitments.id IS NOT NULL)) AS invalid_roots",
                [$instant, $instant, $instant, $instant])->toBase()->first();
        if ($totals === null || (int) $totals->invalid_roots !== 0) {
            throw new RuntimeException('RESERVATION_SUMMARY_INTEGRITY_FAILED');
        }

        return ['committed_principal' => (string) $totals->committed_principal, 'committed_units' => (string) $totals->committed_units,
            'investors' => (int) $totals->investors, 'held_principal' => (string) $totals->held_principal, 'held_units' => (string) $totals->held_units,
            'expired_hold_principal' => (string) $totals->expired_hold_principal, 'expired_hold_units' => (string) $totals->expired_hold_units,
            'returned_principal' => (string) $totals->returned_principal, 'returned_units' => (string) $totals->returned_units,
            'occupied_units' => (string) $totals->occupied_units];
    }
}
