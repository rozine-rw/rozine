<?php

declare(strict_types=1);

namespace App\Domain\Business;

use App\Domain\Underwriting\CashFlowEvidence;
use App\Domain\Underwriting\ExactFinancialValue;
use App\Domain\Underwriting\UnderwritingViolation;
use Brick\Math\BigInteger;

/**
 * @phpstan-import-type MonthInput from CashFlowEvidence
 * @phpstan-import-type ObligationInput from CashFlowEvidence
 * @phpstan-import-type History from \App\Domain\Underwriting\ApplicationUnderwriting
 */
final class ApplicationEvidence
{
    public function __construct(private CashFlowEvidence $cashFlow) {}

    /**
     * Projects only factual aggregates. A read never creates a quote or chooses a principal.
     *
     * @param  list<MonthInput>  $months
     * @param  list<ObligationInput>  $obligations
     * @param  History|null  $history
     * @return array<string, mixed>
     */
    public function project(bool $current, array $months, array $obligations, ?array $history, bool $restricted,
        string $lastCompleteMonth, string $firstRepaymentMonth, int $tenor, string $recurringOwnerDraw, bool $fresh = true): array
    {
        $result = ['period' => null, 'totals' => ['revenue' => null, 'costs' => null, 'net_profit' => null], 'years' => [], 'existing_debt' => null,
            'debt_verified' => false, 'eligibility' => $this->refusal('UNDERWRITING_EVIDENCE_REQUIRED')];
        if (! $current || $months === []) {
            return $result;
        }
        try {
            $result = [...$result, ...$this->aggregate($months)];
            if (! $fresh) {
                return $result;
            }
            if ($history === null) {
                return [...$result, 'eligibility' => $this->refusal('POLICY_INPUT_REQUIRED')];
            }
            if ($history['repeat_eligibility'] !== null && ! $history['has_rozine_history']) {
                throw new UnderwritingViolation('REPEAT_TRACK_INELIGIBLE');
            }
            $facts = $this->cashFlow->analyze($months, $lastCompleteMonth, $firstRepaymentMonth, $tenor,
                $recurringOwnerDraw, $obligations, $history['repeat_eligibility']);
            $result['existing_debt'] = ExactFinancialValue::money(ExactFinancialValue::halfUp($facts['committed_exposure']));
            $result['eligibility'] = $restricted ? $this->refusal('RESTRICTION_ACTIVE') : ['status' => 'eligible'];
        } catch (UnderwritingViolation $failure) {
            $result['eligibility'] = $this->refusal($failure->reasonCode);
        }

        return $result;
    }

    /**
     * @param  non-empty-list<MonthInput>  $months
     * @return array<string, mixed>
     */
    private function aggregate(array $months): array
    {
        usort($months, fn (array $left, array $right): int => strcmp($left['month'], $right['month']));
        $years = $seen = [];
        $revenue = $costs = BigInteger::zero();
        foreach ($months as $month) {
            if (! $month['verified'] || ! preg_match('/^[1-9][0-9]{3}-(0[1-9]|1[0-2])$/D', $month['month']) || isset($seen[$month['month']])) {
                throw new UnderwritingViolation('UNDERWRITING_EVIDENCE_REQUIRED');
            }
            $seen[$month['month']] = true;
            $year = (int) substr($month['month'], 0, 4);
            $inflow = ExactFinancialValue::amount($month['operating_inflow']);
            $outflow = ExactFinancialValue::amount($month['operating_outflow']);
            $prior = $years[$year] ?? ['year' => $year, 'months' => 0, 'revenue' => BigInteger::zero(), 'costs' => BigInteger::zero()];
            $years[$year] = ['year' => $year, 'months' => $prior['months'] + 1, 'revenue' => $prior['revenue']->plus($inflow), 'costs' => $prior['costs']->plus($outflow)];
            $revenue = $revenue->plus($inflow);
            $costs = $costs->plus($outflow);
        }

        return ['period' => ['from_month' => $months[0]['month'], 'through_month' => $months[count($months) - 1]['month'], 'months' => count($months)],
            'totals' => $this->totals($revenue, $costs),
            'years' => array_map(fn (array $year): array => ['year' => $year['year'], 'months' => $year['months'], ...$this->totals($year['revenue'], $year['costs'])], array_values($years))];
    }

    /** @return array<string, mixed> */
    private function totals(BigInteger $revenue, BigInteger $costs): array
    {
        return ['revenue' => ExactFinancialValue::money($revenue), 'costs' => ExactFinancialValue::money($costs),
            'net_profit' => ExactFinancialValue::money($revenue->minus($costs))];
    }

    /** @return array{status: string, code: string, message: string} */
    private function refusal(string $code): array
    {
        return ['status' => 'ineligible', 'code' => $code, 'message' => match ($code) {
            'POLICY_INPUT_REQUIRED' => 'Current credit history and obligations must be verified before applying.',
            'RESTRICTION_ACTIVE' => 'A current restriction prevents a new application.',
            default => 'Complete verified statements and current obligation evidence are required for the applicable 36- or 12-month window.',
        }];
    }
}
