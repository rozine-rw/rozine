<?php

declare(strict_types=1);

namespace App\Infrastructure\Primary;

use App\Application\Operations\Contracts\CanonicalJson;
use App\Application\Primary\Contracts\HeldNoteCapital;
use App\Application\Primary\Contracts\RetainedNoteSchedules;
use App\Application\Primary\RetainedHoldingSchedule;
use Brick\Math\BigInteger;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Outstanding held-note principal from the Holdings a Party owns, authenticated against the
 * complete issued source (the retained Note schedule), as the investor holdings reader does.
 *
 * Coverage is established from the Party's confirmed purchases: every campaign one of them belongs
 * to is checked, so a schedule that issued the Party a Holding the store lacks, a stored Holding
 * the schedule does not carry for that Party and commitment, a principal that disagrees, or an
 * issued campaign without its schedule all fail closed with PRIMARY_ISSUANCE_INTEGRITY_FAILED.
 * Principal is taken from the schedule.
 *
 * Holdings cannot be updated, deleted or reassigned (`protect_primary_holding`). The only way
 * principal comes back is a Business repayment, which servicing pays out to each lot as an
 * immutable `servicing_payouts` row. Outstanding principal is each Holding's scheduled principal
 * less the principal its own lot has been paid. The figure is complete only while every recorded
 * Business repayment has its payouts: a repayment not yet paid out could still reduce principal, so
 * the source reports incomplete and pricing refuses with POLICY_INPUT_REQUIRED rather than guess
 * (QA #831: before, any repayment anywhere left every investor's cart unpriceable).
 */
final readonly class RetainedHeldNoteCapital implements HeldNoteCapital
{
    public const string VERSION = 'held-note-capital-3';

    public function __construct(private CanonicalJson $json, private RetainedNoteSchedules $schedules) {}

    public function current(string $partyId): array
    {
        return DB::transaction(function () use ($partyId): array {
            $campaigns = DB::table('primary_commitments as c')->join('primary_reservations as r', 'r.id', '=', 'c.primary_reservation_id')
                ->where('r.party_id', $partyId)->distinct()->orderBy('r.business_campaign_id')->pluck('r.business_campaign_id');
            $principal = BigInteger::zero();
            $notes = [];
            foreach ($campaigns as $campaignId) {
                foreach ($this->authenticated($partyId, (string) $campaignId) as $note) {
                    $principal = $principal->plus($note['principal']);
                    $notes[] = $note;
                }
            }
            usort($notes, fn (array $left, array $right): int => strcmp($left['id'], $right['id']));
            $repaid = $notes === [] ? '0' : (string) DB::table('servicing_payouts')->where('lot_type', 'primary_holding')
                ->whereIn('lot_id', array_column($notes, 'id'))->sum('principal');
            $complete = ! DB::table('business_repayments as repayment')
                ->whereNotExists(fn ($payouts) => $payouts->from('servicing_payouts')->whereColumn('servicing_payouts.repayment_id', 'repayment.id'))->exists();
            $outstanding = (string) $principal->minus(BigInteger::of($repaid));

            return ['outstanding_principal' => $outstanding, 'revision' => count($notes),
                'sha256' => hash('sha256', $this->json->encode(['version' => self::VERSION, 'party_id' => $partyId, 'notes' => $notes,
                    'repaid_principal' => (string) BigInteger::of($repaid), 'outstanding_principal' => $outstanding, 'complete' => $complete])),
                'complete' => $complete, 'as_of' => now('UTC')->format('Y-m-d\TH:i:s\Z')];
        });
    }

    /** @return list<array{id: string, principal: string}> */
    private function authenticated(string $partyId, string $campaignId): array
    {
        $stored = DB::table('primary_holdings')->where('party_id', $partyId)->where('business_campaign_id', $campaignId)
            ->get(['id', 'commitment_id', 'principal'])->keyBy('id');
        $schedule = $this->schedules->find($campaignId);
        if ($schedule === null) {
            return $stored->isEmpty() ? [] : throw new RuntimeException('PRIMARY_ISSUANCE_INTEGRITY_FAILED');
        }
        $issued = array_values(array_filter($schedule->holdings, fn (RetainedHoldingSchedule $holding): bool => $holding->partyId === $partyId));
        $ids = array_map(fn (RetainedHoldingSchedule $holding): string => $holding->holdingId, $issued);
        if ($schedule->campaignId !== $campaignId || count($issued) !== $stored->count() || count(array_unique($ids)) !== count($ids)) {
            throw new RuntimeException('PRIMARY_ISSUANCE_INTEGRITY_FAILED');
        }

        return array_map(function (RetainedHoldingSchedule $holding) use ($stored): array {
            $row = $stored->get($holding->holdingId);
            if ($row === null || (string) $row->commitment_id !== $holding->commitmentId
                || ! BigInteger::of((string) $row->principal)->isEqualTo($holding->principal)) {
                throw new RuntimeException('PRIMARY_ISSUANCE_INTEGRITY_FAILED');
            }

            return ['id' => $holding->holdingId, 'principal' => (string) BigInteger::of($holding->principal)];
        }, $issued);
    }
}
