<?php

declare(strict_types=1);

namespace App\Infrastructure\Primary;

use App\Application\Business\Contracts\PrimaryCampaignSource;
use App\Application\Operations\Contracts\OperationRecords;
use App\Application\Primary\Contracts\InvestorHoldings;
use App\Models\PrimaryHolding;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Reads `primary_holdings`, which the database keeps immutable and bound to their issued closing,
 * and the confirmed purchases still awaiting one: no Holding, no refund entry and no closure.
 * The issue receipt's operation is the disbursement intent's, from the Holding's own payload, with
 * the request id that operation was recorded under.
 *
 * @phpstan-import-type IssuedHoldingRecord from InvestorHoldings
 */
final readonly class EloquentInvestorHoldings implements InvestorHoldings
{
    public function __construct(private OperationRecords $operations, private PrimaryCampaignSource $campaigns) {}

    public function forInvestor(int $userId): array
    {
        return array_values($this->owned($userId)->orderByDesc('issued_at')->orderByDesc('id')->get()
            ->map(fn (PrimaryHolding $holding): array => $this->record($holding))->all());
    }

    public function find(int $userId, string $holdingId): ?array
    {
        $holding = $this->owned($userId)->whereKey(strtolower($holdingId))->first();

        return $holding === null ? null : $this->record($holding);
    }

    public function ofCommitment(int $userId, string $commitmentId): ?array
    {
        $holding = $this->owned($userId)->where('commitment_id', strtolower($commitmentId))->first();

        return $holding === null ? null : $this->record($holding);
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

    /** @return IssuedHoldingRecord */
    private function record(PrimaryHolding $holding): array
    {
        $money = fn (mixed $amount): array => ['currency' => 'RWF', 'amount' => (string) $amount];
        /** @var IssuedHoldingRecord['rights'] $rights */
        $rights = $holding->rights;
        /** @var IssuedHoldingRecord['terms'] $terms */
        $terms = $holding->terms;
        /** @var array{receipt: array{receipt_id: string, operation_id: string}} $payload */
        $payload = $holding->payload;

        return ['id' => $holding->id, 'campaign_id' => $holding->business_campaign_id, 'commitment_id' => $holding->commitment_id,
            'units' => (string) $holding->units, 'principal' => $money($holding->principal),
            'ordinals' => array_map(fn (array $range): array => ['first' => (string) $range['first'], 'last' => (string) $range['last']], $holding->ordinals),
            'rights' => $rights, 'terms' => $terms,
            'schedule' => array_map(fn (array $entry): array => ['index' => (int) $entry['index'], 'due_on' => (string) $entry['due_date'],
                'principal' => $entry['principal'], 'return' => $entry['return']], $holding->schedule),
            'issued_at' => $holding->issued_at->utc()->toIso8601String(), 'disbursement_effective_at' => $holding->disbursement_effective_at->utc()->toIso8601String(),
            'effective_date' => $holding->effective_date->format('Y-m-d'),
            'receipt' => ['receipt_id' => $holding->receipt_id, 'operation_id' => $payload['receipt']['operation_id'],
                'request_id' => $this->operations->requestOf($payload['receipt']['operation_id']) ?? throw new RuntimeException('HOLDING_ISSUE_RECEIPT_UNAVAILABLE')]];
    }
}
