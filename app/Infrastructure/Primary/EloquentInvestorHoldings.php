<?php

declare(strict_types=1);

namespace App\Infrastructure\Primary;

use App\Application\Operations\Contracts\OperationRecords;
use App\Application\Primary\Contracts\InvestorHoldings;
use App\Models\PrimaryHolding;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use RuntimeException;

/**
 * Reads `primary_holdings`, which the database keeps immutable and bound to their issued closing.
 * The issue receipt's operation is the disbursement intent's, from the Holding's own payload, with
 * the request id that operation was recorded under.
 *
 * @phpstan-import-type IssuedHoldingRecord from InvestorHoldings
 */
final readonly class EloquentInvestorHoldings implements InvestorHoldings
{
    public function __construct(private OperationRecords $operations) {}

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
