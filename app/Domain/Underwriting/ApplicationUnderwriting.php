<?php

declare(strict_types=1);

namespace App\Domain\Underwriting;

use Brick\Math\BigRational;

/**
 * Composes only server-resolved facts. The persistence adapter must hold evidence, authority,
 * exposure and restrictions through publication; this record does not grant borrowing authority.
 *
 * @phpstan-import-type MonthInput from CashFlowEvidence
 * @phpstan-import-type ObligationInput from CashFlowEvidence
 * @phpstan-import-type RepeatEligibility from CashFlowEvidence
 *
 * @phpstan-type Conduct array{on_time: int, total: int}
 * @phpstan-type History array{has_rozine_history: bool, repeat_eligibility: RepeatEligibility|null, instalment_conduct: Conduct|null, report_conduct: Conduct|null, post_grace_arrears: bool, reporting_breach: bool, defaulted: bool, days_past_due: int}
 * @phpstan-type Inputs array{requested_principal: string, tenor_months: int, accepted_principal: string|null, months: list<MonthInput>, last_complete_month: string, first_repayment_month: string, recurring_owner_draw: string, obligations: list<ObligationInput>, history: History|null, restriction_active: bool, accepted_commitments?: list<array{id: string, principal: string}>}
 */
final class ApplicationUnderwriting
{
    public const VERSION = 'application-underwriting-2';

    public function __construct(
        private CashFlowEvidence $evidence,
        private EngineScorecard $scorecard,
        private FlatReturnPricing $pricing,
        private LoanCapacity $capacity,
    ) {}

    /**
     * Private replay record, including exact ratios and internal score components. Public quote
     * Resources must explicitly select their permitted facts rather than serialize this record.
     *
     * @param  Inputs  $inputs
     * @return array<string, mixed>
     */
    public function evaluate(array $inputs): array
    {
        $result = ['calculation_version' => self::VERSION, 'policy_version' => FlatReturnPricing::POLICY_VERSION,
            'inputs' => $inputs, 'eligible' => false, 'code' => 'POLICY_INPUT_REQUIRED',
            'cash_flow' => null, 'scorecard' => null, 'pricing' => null, 'maximum_capacity' => null, 'capacity' => null];
        FlatReturnPricing::validateTenor($inputs['tenor_months']);
        if (ExactFinancialValue::amount($inputs['requested_principal'])->isLessThan(3000000)) {
            throw new UnderwritingViolation('INVALID_LOAN_TERMS');
        }
        if ($inputs['restriction_active']) {
            return [...$result, 'code' => 'RESTRICTION_ACTIVE'];
        }
        $history = $inputs['history'];
        if ($history === null) {
            return $result;
        }
        try {
            if ($history['repeat_eligibility'] !== null && ! $history['has_rozine_history']) {
                throw new UnderwritingViolation('REPEAT_TRACK_INELIGIBLE');
            }
            $facts = $this->evidence->analyze($inputs['months'], $inputs['last_complete_month'], $inputs['first_repayment_month'], $inputs['tenor_months'],
                $inputs['recurring_owner_draw'], $inputs['obligations'], $history['repeat_eligibility'], $inputs['accepted_commitments'] ?? []);
        } catch (UnderwritingViolation $failure) {
            return [...$result, 'code' => 'UNDERWRITING_EVIDENCE_REQUIRED', 'evidence_reason' => $failure->reasonCode];
        }
        $result['cash_flow'] = $this->replayValues($facts);
        $score = $this->scorecard->evaluate($facts['coverage'], $facts['cfads_margin'], $facts['relative_mad'], $facts['positive_months'], $facts['verified_months'],
            $history['has_rozine_history'], $history['instalment_conduct'], $history['report_conduct'], $history['post_grace_arrears'],
            $history['reporting_breach'], $history['defaulted'], $history['days_past_due']);
        $result['scorecard'] = $score;
        if ($score['rating'] === null) {
            return [...$result, 'code' => 'UNDERWRITING_EVIDENCE_REQUIRED'];
        }
        $pricing = $this->pricing->calculate($score['rating'], $inputs['tenor_months']);
        $rate = BigRational::ofFraction($pricing['fraction']['numerator'], $pricing['fraction']['denominator']);
        $result['pricing'] = $pricing;
        $monthly = array_column($facts['repeat_projections'], 'cfads');
        $maximum = $this->capacity->evaluate($inputs['requested_principal'], $inputs['tenor_months'], $rate, $facts['cfads'], $facts['remaining_room'], $monthly);
        $result['maximum_capacity'] = $maximum;
        if (! $maximum['eligible']) {
            return [...$result, 'code' => $maximum['code'], 'capacity' => $maximum];
        }
        $selected = $inputs['accepted_principal'] === null ? $maximum : $this->capacity->evaluate($inputs['requested_principal'], $inputs['tenor_months'], $rate,
            $facts['cfads'], $facts['remaining_room'], $monthly, $inputs['accepted_principal']);

        return [...$result, 'eligible' => true, 'code' => $selected['code'], 'capacity' => $selected];
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function replayValues(array $values): array
    {
        return array_map(function (mixed $value): mixed {
            if ($value instanceof BigRational) {
                return ExactFinancialValue::ratio($value);
            }

            return is_array($value) ? $this->replayValues($value) : $value;
        }, $values);
    }
}
