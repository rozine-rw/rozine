<?php

declare(strict_types=1);

namespace App\Domain\Auditor;

use App\Domain\Underwriting\ExactFinancialValue;
use Brick\Math\BigInteger;
use Brick\Math\RoundingMode;

/**
 * @phpstan-import-type VerifiedObservation from \App\Domain\Evidence\StatementAuditReview
 * @phpstan-import-type Statement from \App\Domain\Evidence\StatementReconciliation
 *
 * @phpstan-type Monthly array{period: string, inflow: string, outflow: string, net: string, cover: string|null, closing_balance: string, source_ids: list<string>, rail_ids: list<string>}
 */
final class AuditMonthlyFacts
{
    /**
     * @param  list<VerifiedObservation>  $observations
     * @param  list<Statement>  $statements
     * @return Monthly|null
     */
    public function project(?string $period, array $observations, array $statements): ?array
    {
        if ($period === null) {
            return null;
        }
        $months = array_values(array_filter($observations, fn (array $month): bool => $month['month'] === $period));
        if (count($months) !== 1 || $months[0]['source_ids'] === []) {
            return null;
        }
        $month = $months[0];
        $rows = array_values(array_filter($statements, fn (array $row): bool => $row['month'] === $period));
        $rails = array_column($rows, 'rail_id');
        $expected = $month['rail_ids'];
        sort($rails);
        sort($expected);
        if ($rails === [] || $rails !== $expected || count(array_unique($rails)) !== count($rails)) {
            return null;
        }
        $closing = BigInteger::zero();
        foreach ($rows as $row) {
            $closing = $closing->plus(ExactFinancialValue::amount($row['closing_balance']));
        }
        $inflow = ExactFinancialValue::amount($month['operating_inflow']);
        $outflow = ExactFinancialValue::amount($month['operating_outflow']);
        $denominator = $outflow->plus(ExactFinancialValue::amount($month['debt_service']));

        return ['period' => $period, 'inflow' => (string) $inflow, 'outflow' => (string) $outflow,
            'net' => (string) $inflow->minus($outflow), 'cover' => $denominator->isZero() ? null
                : (string) $inflow->toBigRational()->dividedBy($denominator)->toScale(2, RoundingMode::HalfUp),
            'closing_balance' => (string) $closing, 'source_ids' => $month['source_ids'], 'rail_ids' => $rails];
    }
}
