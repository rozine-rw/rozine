<?php

declare(strict_types=1);

namespace App\Domain\Underwriting;

use Brick\Math\BigRational;

/**
 * Consumes immutable, reconciled observations selected by the evidence adapter, never request facts.
 *
 * @phpstan-type MonthInput array{month: string, operating_inflow: string, operating_outflow: string, owner_draw: string, debt_service: string, verified: bool}
 * @phpstan-type ObligationInput array{id: string, principal: string, service_by_month: array<string, string>}
 * @phpstan-type RepeatEligibility array{baseline_passed: bool, settled_notes: int, late_payments: int, gap_audits_complete: bool, automated_collection: bool}
 */
final class CashFlowEvidence
{
    /**
     * @param  list<MonthInput>  $months
     * @param  list<ObligationInput>  $obligations
     * @param  RepeatEligibility|null  $repeatEligibility  Null selects the first-time 36-month window.
     * @return array<string, mixed>
     */
    public function analyze(
        array $months,
        string $lastCompleteMonth,
        string $firstRepaymentMonth,
        int $tenor,
        string $recurringOwnerDraw,
        array $obligations,
        ?array $repeatEligibility = null,
    ): array {
        FlatReturnPricing::validateTenor($tenor);
        $last = $this->monthIndex($lastCompleteMonth);
        $firstRepayment = $this->monthIndex($firstRepaymentMonth);
        $required = $repeatEligibility === null ? 36 : 12;
        if (count($months) !== $required || $firstRepayment <= $last) {
            throw new UnderwritingViolation('UNDERWRITING_EVIDENCE_REQUIRED');
        }
        if ($repeatEligibility !== null && (! $repeatEligibility['baseline_passed'] || $repeatEligibility['settled_notes'] < 1
            || $repeatEligibility['late_payments'] !== 0 || ! $repeatEligibility['gap_audits_complete'] || ! $repeatEligibility['automated_collection'])) {
            throw new UnderwritingViolation('REPEAT_TRACK_INELIGIBLE');
        }
        usort($months, fn (array $left, array $right): int => strcmp($left['month'], $right['month']));
        $inflow = $outflow = $draw = $debt = BigRational::zero();
        $nocf = $revenue = [];
        $positive = 0;
        foreach ($months as $index => $month) {
            if (! $month['verified'] || $this->monthIndex($month['month']) !== $last - $required + 1 + $index) {
                throw new UnderwritingViolation('UNDERWRITING_EVIDENCE_REQUIRED');
            }
            $monthInflow = ExactFinancialValue::amount($month['operating_inflow'])->toBigRational();
            $monthOutflow = ExactFinancialValue::amount($month['operating_outflow'])->toBigRational();
            $inflow = $inflow->plus($monthInflow);
            $outflow = $outflow->plus($monthOutflow);
            $draw = $draw->plus(ExactFinancialValue::amount($month['owner_draw']));
            $debt = $debt->plus(ExactFinancialValue::amount($month['debt_service']));
            $monthNocf = $monthInflow->minus($monthOutflow);
            $nocf[] = $monthNocf;
            $revenue[] = $monthInflow;
            if ($monthNocf->isPositive()) {
                $positive++;
            }
        }
        $repaymentMonths = array_map($this->monthLabel(...), range($firstRepayment, $firstRepayment + $tenor - 1));
        [$exposure, $contractualDebt] = $this->obligations($obligations, $repaymentMonths);
        $meanDebt = $debt->dividedBy($required);
        $existingService = BigRational::max($meanDebt, ...array_values($contractualDebt));
        $ownerDraw = BigRational::max($draw->dividedBy($required), ExactFinancialValue::amount($recurringOwnerDraw));
        $meanNocf = $inflow->minus($outflow)->dividedBy($required);
        $cfads = $meanNocf->minus($existingService)->minus($ownerDraw);
        $ttm = BigRational::sum(...array_slice($revenue, -12));
        $exposureLimit = BigRational::min($ttm->multipliedBy('7/20'), 100000000);
        /** @var non-empty-list<BigRational> $nocf */
        $median = ExactFinancialValue::median($nocf);
        $deviations = array_map(fn (BigRational $value): BigRational => $value->minus($median)->abs(), $nocf);
        $relativeMad = ExactFinancialValue::median($deviations)->dividedBy(BigRational::max($median->abs(), 1));
        $coverageDenominator = $outflow->plus($debt);
        $meanInflow = $inflow->dividedBy($required);
        $projections = [];
        if ($repeatEligibility !== null) {
            /** @var non-empty-list<BigRational> $revenue */
            $projectionCap = ExactFinancialValue::median($revenue)->multipliedBy('6/5');
            foreach ($repaymentMonths as $label) {
                $corresponding = ($this->monthIndex($label) - ($last - 11)) % 12;
                $projectedNocf = BigRational::min($nocf[$corresponding], $projectionCap);
                $projectedDebt = BigRational::max($meanDebt, $contractualDebt[$label]);
                $projections[] = [
                    'month' => $label,
                    'source_month' => $months[$corresponding]['month'],
                    'nocf' => $projectedNocf,
                    'owner_draw' => $ownerDraw,
                    'existing_debt_service' => $projectedDebt,
                    'cfads' => $projectedNocf->minus($ownerDraw)->minus($projectedDebt),
                ];
            }
        }

        return [
            'verified_months' => $required,
            'positive_months' => $positive,
            'mean_nocf' => $meanNocf,
            'mean_operating_inflow' => $meanInflow,
            'owner_draw' => $ownerDraw,
            'existing_debt_service' => $existingService,
            'cfads' => $cfads,
            'coverage' => $coverageDenominator->isZero() ? null : $inflow->dividedBy($coverageDenominator),
            'cfads_margin' => $meanInflow->isZero() ? null : $cfads->dividedBy($meanInflow),
            'relative_mad' => $relativeMad,
            'ttm_revenue' => $ttm,
            'exposure_limit' => $exposureLimit,
            'committed_exposure' => $exposure,
            'remaining_room' => $exposureLimit->minus($exposure),
            'repeat_projections' => $projections,
        ];
    }

    /**
     * @param  list<ObligationInput>  $obligations
     * @param  list<string>  $repaymentMonths
     * @return array{BigRational, array<string, BigRational>}
     */
    private function obligations(array $obligations, array $repaymentMonths): array
    {
        $seen = [];
        $exposure = BigRational::zero();
        $schedule = array_fill_keys($repaymentMonths, BigRational::zero());
        foreach ($obligations as $obligation) {
            $principal = ExactFinancialValue::amount($obligation['principal']);
            $service = $obligation['service_by_month'];
            ksort($service);
            if ($obligation['id'] === '' || (isset($seen[$obligation['id']]) && $seen[$obligation['id']] !== [(string) $principal, $service])) {
                throw new UnderwritingViolation('OBLIGATION_EVIDENCE_CONFLICT');
            }
            if (isset($seen[$obligation['id']])) {
                continue;
            }
            $seen[$obligation['id']] = [(string) $principal, $service];
            $exposure = $exposure->plus($principal);
            foreach ($repaymentMonths as $month) {
                if (! array_key_exists($month, $service)) {
                    throw new UnderwritingViolation('UNDERWRITING_EVIDENCE_REQUIRED');
                }
                $schedule[$month] = $schedule[$month]->plus(ExactFinancialValue::amount($service[$month]));
            }
        }

        return [$exposure, $schedule];
    }

    private function monthIndex(string $month): int
    {
        if (! preg_match('/^([1-9][0-9]{3})-(0[1-9]|1[0-2])$/D', $month, $parts)) {
            throw new UnderwritingViolation('INVALID_OBSERVATION_MONTH');
        }

        return (int) $parts[1] * 12 + (int) $parts[2] - 1;
    }

    private function monthLabel(int $index): string
    {
        return sprintf('%04d-%02d', intdiv($index, 12), $index % 12 + 1);
    }
}
