<?php

declare(strict_types=1);

namespace App\Infrastructure\Primary;

use App\Application\Business\Contracts\PrimaryCampaignSource;
use App\Application\Operations\Contracts\OperationRecords;
use App\Application\Primary\Contracts\InvestorHoldings;
use App\Application\Primary\Contracts\RetainedNoteSchedules;
use App\Application\Primary\RetainedHoldingSchedule;
use App\Application\Primary\RetainedNoteInstalment;
use App\Application\Primary\RetainedNoteSchedule;
use App\Models\PrimaryHolding;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Selects the Party's `primary_holdings` rows, then builds every figure from the complete
 * authenticated issued source (the retained Note schedule) in one read transaction: a Holding the
 * source does not carry for that Party, or any native/envelope disagreement, fails closed. Also
 * lists the confirmed purchases still awaiting one: no Holding, no refund entry and no closure.
 * The issue receipt's operation is the disbursement intent's, with the request id that operation
 * was recorded under.
 *
 * @phpstan-import-type IssuedHoldingRecord from InvestorHoldings
 */
final readonly class EloquentInvestorHoldings implements InvestorHoldings
{
    public function __construct(private OperationRecords $operations, private PrimaryCampaignSource $campaigns,
        private RetainedNoteSchedules $schedules) {}

    public function forInvestor(int $userId): array
    {
        return DB::transaction(function () use ($userId): array {
            $snapshots = [];

            return array_values($this->owned($userId)->orderByDesc('issued_at')->orderByDesc('id')->get()
                ->map(function (PrimaryHolding $holding) use (&$snapshots): array {
                    $snapshots[$holding->business_campaign_id] ??= $this->snapshot($holding->business_campaign_id);

                    return $this->record($holding, $snapshots[$holding->business_campaign_id]);
                })->all());
        });
    }

    public function find(int $userId, string $holdingId): ?array
    {
        return DB::transaction(function () use ($userId, $holdingId): ?array {
            $holding = $this->owned($userId)->whereKey(strtolower($holdingId))->first();

            return $holding === null ? null : $this->record($holding, $this->snapshot($holding->business_campaign_id));
        });
    }

    public function ofCommitment(int $userId, string $commitmentId): ?array
    {
        return DB::transaction(function () use ($userId, $commitmentId): ?array {
            $holding = $this->owned($userId)->where('commitment_id', strtolower($commitmentId))->first();

            return $holding === null ? null : $this->record($holding, $this->snapshot($holding->business_campaign_id));
        });
    }

    public function awaitingIssue(int $userId): array
    {
        $rows = DB::select("SELECT c.id, r.business_campaign_id, r.units, r.principal,
                EXISTS (SELECT 1 FROM primary_campaign_fundings f WHERE f.business_campaign_id = r.business_campaign_id) AS funded
            FROM primary_commitments c JOIN primary_reservations r ON r.id = c.primary_reservation_id
            WHERE r.party_id = (SELECT party_id FROM users WHERE id = ?)
                AND NOT EXISTS (SELECT 1 FROM primary_holdings h WHERE h.commitment_id = c.id)
                AND NOT EXISTS (SELECT 1 FROM ledger_entries e WHERE e.source_type = 'primary_reservation' AND e.source_id = r.id AND e.kind = 'primary_refund')
                AND NOT EXISTS (SELECT 1 FROM business_campaign_closures k WHERE k.business_campaign_id = r.business_campaign_id)
            ORDER BY c.id DESC", [$userId]);

        $titles = [];
        $pending = [];
        foreach ($rows as $row) {
            $row = (array) $row;
            $campaignId = (string) $row['business_campaign_id'];
            $titles[$campaignId] ??= $this->campaigns->presentation($campaignId)['title'];
            $pending[] = ['commitment_id' => (string) $row['id'], 'campaign_id' => $campaignId, 'deal_name' => $titles[$campaignId], 'units' => (string) $row['units'],
                'principal' => ['currency' => 'RWF', 'amount' => (string) $row['principal']], 'funded' => (bool) $row['funded']];
        }

        return $pending;
    }

    /** @return Builder<PrimaryHolding> */
    private function owned(int $userId): Builder
    {
        return PrimaryHolding::query()->where('party_id', User::query()->whereKey($userId)->whereNotNull('party_id')->select('party_id'));
    }

    private function snapshot(string $campaignId): RetainedNoteSchedule
    {
        return $this->schedules->find($campaignId) ?? throw new RuntimeException('PRIMARY_ISSUANCE_INTEGRITY_FAILED');
    }

    /** @return IssuedHoldingRecord */
    private function record(PrimaryHolding $holding, RetainedNoteSchedule $snapshot): array
    {
        $issued = array_values(array_filter($snapshot->holdings, fn (RetainedHoldingSchedule $candidate): bool => $candidate->holdingId === $holding->id));
        $source = count($issued) === 1 ? $issued[0] : null;
        if ($source === null || $source->partyId !== $holding->party_id || $source->commitmentId !== $holding->commitment_id
            || $snapshot->campaignId !== $holding->business_campaign_id) {
            throw new RuntimeException('PRIMARY_ISSUANCE_INTEGRITY_FAILED');
        }
        $money = fn (string $amount): array => ['currency' => 'RWF', 'amount' => $amount];
        /** @var IssuedHoldingRecord['rights'] $rights */
        $rights = $source->rights;
        /** @var IssuedHoldingRecord['terms'] $terms */
        $terms = $source->terms;
        /** @var array{receipt: array{receipt_id: string, operation_id: string}} $payload */
        $payload = $holding->payload;

        return ['id' => $source->holdingId, 'campaign_id' => $snapshot->campaignId, 'commitment_id' => $source->commitmentId,
            'units' => (string) $source->units, 'principal' => $money($source->principal),
            'ordinals' => array_map(fn (array $range): array => ['first' => (string) $range['first'], 'last' => (string) $range['last']], $source->ordinals),
            'rights' => $rights, 'terms' => $terms,
            'schedule' => array_map(fn (RetainedNoteInstalment $instalment): array => ['index' => $instalment->index, 'due_on' => $instalment->dueDate,
                'principal' => $money($instalment->principal), 'return' => $money($instalment->contractualReturn)], $source->instalments),
            'issued_at' => $holding->issued_at->utc()->toIso8601String(), 'disbursement_effective_at' => $holding->disbursement_effective_at->utc()->toIso8601String(),
            'effective_date' => $snapshot->closing->effectiveDate ?? throw new RuntimeException('PRIMARY_ISSUANCE_INTEGRITY_FAILED'),
            'receipt' => ['receipt_id' => $holding->receipt_id, 'operation_id' => $payload['receipt']['operation_id'],
                'request_id' => $this->operations->requestOf($payload['receipt']['operation_id']) ?? throw new RuntimeException('HOLDING_ISSUE_RECEIPT_UNAVAILABLE')]];
    }
}
