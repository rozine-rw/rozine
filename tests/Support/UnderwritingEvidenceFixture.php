<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Domain\Underwriting\CashFlowEvidence;
use DateTimeImmutable;

/**
 * @phpstan-import-type MonthInput from CashFlowEvidence
 * @phpstan-import-type RepeatEligibility from CashFlowEvidence
 */
final class UnderwritingEvidenceFixture
{
    /** @return non-empty-list<MonthInput> */
    public static function months(int $count = 36, string $inflow = '4000000', string $outflow = '1000000'): array
    {
        $first = (new DateTimeImmutable('2026-08-01'))->modify('-'.($count - 1).' months');

        return array_map(fn (int $offset): array => [
            'month' => $first->modify('+'.$offset.' months')->format('Y-m'),
            'operating_inflow' => $inflow, 'operating_outflow' => $outflow,
            'owner_draw' => '0', 'debt_service' => '0', 'verified' => true,
        ], range(0, $count - 1));
    }

    /** @return RepeatEligibility */
    public static function repeat(): array
    {
        return ['baseline_passed' => true, 'settled_notes' => 1, 'late_payments' => 0, 'gap_audits_complete' => true, 'automated_collection' => true];
    }

    /** @return array<string, string> */
    public static function debt(string $amount): array
    {
        return array_fill_keys(['2026-09', '2026-10', '2026-11', '2026-12', '2027-01', '2027-02'], $amount);
    }
}
