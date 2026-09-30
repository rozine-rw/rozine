<?php

declare(strict_types=1);

namespace App\Infrastructure\Primary;

use App\Application\Primary\Contracts\CampaignReservationSummary;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use DateTimeImmutable;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
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
        $refunds = DB::table('ledger_entries as entries')
            ->where('entries.kind', 'primary_refund')->where('entries.source_type', 'primary_reservation')
            ->whereIn('entries.source_id', PrimaryReservationRecord::query()->where('business_campaign_id', $campaignId)->select('id'))
            ->leftJoin('ledger_lines as lines', 'lines.entry_id', 'entries.id')
            ->leftJoin('ledger_accounts as accounts', 'accounts.id', 'lines.account_id')
            ->groupBy('entries.id', 'entries.source_id', 'entries.wallet_id', 'entries.origin_operation_id', 'entries.currency')
            ->select('entries.id', 'entries.source_id', 'entries.wallet_id', 'entries.origin_operation_id', 'entries.currency')
            ->selectRaw("COUNT(lines.id) AS line_count,
                COALESCE(SUM(lines.amount) FILTER (WHERE lines.direction = 'credit' AND accounts.kind = 'investor_available'
                    AND accounts.wallet_id = entries.wallet_id AND accounts.currency = 'RWF'), 0) AS credited,
                COALESCE(SUM(lines.amount) FILTER (WHERE lines.direction = 'debit' AND accounts.kind = 'investor_committed'
                    AND accounts.wallet_id = entries.wallet_id AND accounts.currency = 'RWF'), 0) AS debited");
        $totals = PrimaryReservationRecord::query()->from('primary_reservations as reservations')
            ->where('reservations.business_campaign_id', $campaignId)
            ->leftJoinSub($latest, 'latest', fn (JoinClause $join): JoinClause => $join->on('latest.primary_reservation_id', 'reservations.id'))
            ->leftJoin('primary_reservation_versions as versions', fn (JoinClause $join): JoinClause => $join
                ->on('versions.primary_reservation_id', 'reservations.id')->on('versions.revision', 'latest.revision'))
            ->leftJoin('primary_commitments as commitments', 'commitments.primary_reservation_id', 'reservations.id')
            ->leftJoinSub($refunds, 'refunds', fn (JoinClause $join): JoinClause => $join->on('refunds.source_id', 'reservations.id'))
            ->leftJoin('investor_wallets as refund_wallets', 'refund_wallets.id', 'refunds.wallet_id')
            ->selectRaw("COALESCE(SUM(reservations.principal) FILTER (WHERE versions.state = 'confirmed' AND refunds.id IS NULL), 0)::text AS committed_principal,
                COALESCE(SUM(reservations.units) FILTER (WHERE versions.state = 'confirmed' AND refunds.id IS NULL), 0)::text AS committed_units,
                COUNT(DISTINCT reservations.party_id) FILTER (WHERE versions.state = 'confirmed' AND refunds.id IS NULL) AS investors,
                COALESCE(SUM(reservations.principal) FILTER (WHERE versions.state = 'held' AND reservations.expires_at > ?), 0)::text AS held_principal,
                COALESCE(SUM(reservations.units) FILTER (WHERE versions.state = 'held' AND reservations.expires_at > ?), 0)::text AS held_units,
                COALESCE(SUM(reservations.principal) FILTER (WHERE versions.state = 'held' AND reservations.expires_at <= ?), 0)::text AS expired_hold_principal,
                COALESCE(SUM(reservations.units) FILTER (WHERE versions.state = 'held' AND reservations.expires_at <= ?), 0)::text AS expired_hold_units,
                COALESCE(SUM(reservations.principal) FILTER (WHERE versions.state IN ('released', 'expired') OR refunds.id IS NOT NULL), 0)::text AS returned_principal,
                COALESCE(SUM(reservations.units) FILTER (WHERE versions.state IN ('released', 'expired') OR refunds.id IS NOT NULL), 0)::text AS returned_units,
                COALESCE(SUM(reservations.units), 0)::text AS occupied_units,
                COUNT(*) FILTER (WHERE versions.state IS NULL OR (versions.state = 'confirmed') <> (commitments.id IS NOT NULL)
                    OR (refunds.id IS NOT NULL AND (versions.state IS DISTINCT FROM 'confirmed'
                        OR refund_wallets.party_id IS DISTINCT FROM reservations.party_id
                        OR refunds.origin_operation_id IS DISTINCT FROM reservations.origin_operation_id
                        OR refunds.currency IS DISTINCT FROM 'RWF' OR refunds.line_count <> 2
                        OR refunds.credited <> reservations.principal OR refunds.debited <> reservations.principal))) AS invalid_roots",
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
