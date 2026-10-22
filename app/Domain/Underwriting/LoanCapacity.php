<?php

declare(strict_types=1);

namespace App\Domain\Underwriting;

use Brick\Math\BigInteger;
use Brick\Math\BigRational;

/** Arithmetic eligibility; the application must still authorize and lock current evidence/exposure. */
final class LoanCapacity
{
    public const CALCULATOR_VERSION = 'underwriting-1';

    /** The coverage every offer is sized to (DSCR 1.50). */
    public const string TARGET_COVERAGE = '3/2';

    /** Below this coverage (DSCR 1.25) an application is rejected. */
    public const string MINIMUM_COVERAGE = '5/4';

    /** The smallest and largest principal one note may raise, in RWF. */
    public const int MINIMUM_PRINCIPAL = 3000000;

    public const int MAXIMUM_PRINCIPAL = 100000000;

    /** Every repeat month must cover its instalment at least this well (DSCR 1.35). */
    public const string REPEAT_MONTHLY_COVERAGE = '27/20';

    /** Offers are whole issuance units of this many RWF (one note). */
    public const int ISSUANCE_UNIT = 5000;

    /**
     * @param  list<BigRational>  $repeatMonthlyCfads  The authorized corresponding-month projection, or [] for first-time.
     * @return array<string, mixed>
     */
    public function evaluate(
        string $requestedPrincipal,
        int $tenor,
        BigRational $rate,
        BigRational $cfads,
        BigRational $remainingRoom,
        array $repeatMonthlyCfads = [],
        ?string $acceptedPrincipal = null,
    ): array {
        $original = LoanSchedule::build($requestedPrincipal, $tenor, $rate);
        if ($repeatMonthlyCfads !== [] && count($repeatMonthlyCfads) !== $tenor) {
            throw new UnderwritingViolation('UNDERWRITING_EVIDENCE_REQUIRED');
        }
        $initialDscr = $original->dscr($cfads);
        $result = [
            'policy_version' => FlatReturnPricing::POLICY_VERSION,
            'calculator_version' => self::CALCULATOR_VERSION,
            'code' => 'DSCR_BELOW_CUTOFF',
            'eligible' => false,
            'original_schedule' => $original->toArray(),
            'original_dscr' => ExactFinancialValue::ratio($initialDscr),
            'remaining_room' => ExactFinancialValue::ratio($remainingRoom),
            'raw_capacity' => null,
            'rounded_candidate' => null,
            'guarded_principal' => null,
            'rounding_guard' => null,
            'offer' => null,
            'final_dscr' => null,
            'repeat_monthly_dscr' => [],
            'reason_codes' => [],
        ];
        if ($initialDscr->isLessThan(self::MINIMUM_COVERAGE)) {
            return $result;
        }

        $rawCapacity = $cfads->dividedBy(self::TARGET_COVERAGE)->multipliedBy($tenor)->dividedBy($rate->plus(1));
        $result['raw_capacity'] = ExactFinancialValue::ratio($rawCapacity);
        if (BigRational::min($rawCapacity, $remainingRoom, $requestedPrincipal)->isLessThan(self::MINIMUM_PRINCIPAL)) {
            $result['code'] = 'CAPACITY_BELOW_MINIMUM';

            return $result;
        }

        $candidate = BigInteger::min(
            ExactFinancialValue::halfUp($rawCapacity), $requestedPrincipal, self::MAXIMUM_PRINCIPAL,
            ExactFinancialValue::floor($remainingRoom),
        );
        $result['rounded_candidate'] = ExactFinancialValue::money($candidate);
        $schedule = LoanSchedule::build((string) $candidate, $tenor, $rate);
        while ($schedule->dscr($cfads)->isLessThan(self::TARGET_COVERAGE)) {
            $candidate = $candidate->minus(1);
            $schedule = LoanSchedule::build((string) $candidate, $tenor, $rate);
        }
        $result['guarded_principal'] = ExactFinancialValue::money($candidate);
        $guard = BigInteger::of($result['rounded_candidate']['amount'])->minus($candidate);
        $result['rounding_guard'] = ExactFinancialValue::money($guard);
        $reasons = [];
        if ($rawCapacity->isLessThan($requestedPrincipal)) {
            $reasons[] = 'DSCR_SCALED';
        }
        if ($remainingRoom->isLessThan(BigRational::min($rawCapacity, $requestedPrincipal, self::MAXIMUM_PRINCIPAL))) {
            $reasons[] = 'EXPOSURE_CAPPED';
        }
        if ($original->principal->isGreaterThan(self::MAXIMUM_PRINCIPAL)) {
            $reasons[] = 'PRINCIPAL_MAX_CAPPED';
        }
        if (! $guard->isZero()) {
            $reasons[] = 'ROUNDING_GUARD';
        }
        $quantized = $candidate->quotient(self::ISSUANCE_UNIT)->multipliedBy(self::ISSUANCE_UNIT);
        if (! $quantized->isEqualTo($candidate)) {
            $reasons[] = 'ISSUANCE_UNIT_QUANTIZED';
        }
        if ($repeatMonthlyCfads !== []) {
            $repeatPrincipal = $this->repeatPrincipal($quantized, $tenor, $rate, $repeatMonthlyCfads);
            if ($repeatPrincipal->isLessThan($quantized)) {
                $reasons[] = 'REPEAT_MONTHLY_DSCR_SCALED';
            }
            $quantized = $repeatPrincipal;
        }
        $result['reason_codes'] = $reasons;
        if ($quantized->isLessThan(self::MINIMUM_PRINCIPAL)) {
            $result['code'] = 'CAPACITY_BELOW_MINIMUM';

            return $result;
        }

        if ($acceptedPrincipal !== null) {
            $accepted = ExactFinancialValue::amount($acceptedPrincipal);
            if ($accepted->isLessThan(self::MINIMUM_PRINCIPAL) || $accepted->isGreaterThan($quantized) || ! $accepted->remainder(self::ISSUANCE_UNIT)->isZero()) {
                throw new UnderwritingViolation('INVALID_ACCEPTED_PRINCIPAL');
            }
            $quantized = $accepted;
        }
        $final = LoanSchedule::build((string) $quantized, $tenor, $rate);
        $result['code'] = 'UNDERWRITING_ELIGIBLE';
        $result['eligible'] = true;
        $result['offer'] = [...$final->toArray(), 'units' => (string) $quantized->quotient(self::ISSUANCE_UNIT)];
        $result['final_dscr'] = ExactFinancialValue::ratio($final->dscr($cfads));
        $result['repeat_monthly_dscr'] = array_map(
            fn (BigRational $monthly, BigInteger $instalment): array => ExactFinancialValue::ratio($monthly->dividedBy($instalment)),
            $repeatMonthlyCfads, $repeatMonthlyCfads === [] ? [] : $final->instalments,
        );

        return $result;
    }

    /** @param list<BigRational> $monthlyCfads */
    private function repeatPrincipal(BigInteger $maximum, int $tenor, BigRational $rate, array $monthlyCfads): BigInteger
    {
        if (! $this->passesMonthly(LoanSchedule::build((string) self::MINIMUM_PRINCIPAL, $tenor, $rate), $monthlyCfads)) {
            return BigInteger::zero();
        }
        $low = 600;
        $high = $maximum->quotient(self::ISSUANCE_UNIT)->toInt();
        while ($low < $high) {
            $middle = intdiv($low + $high + 1, 2);
            $schedule = LoanSchedule::build((string) ($middle * self::ISSUANCE_UNIT), $tenor, $rate);
            if ($this->passesMonthly($schedule, $monthlyCfads)) {
                $low = $middle;
            } else {
                $high = $middle - 1;
            }
        }

        return BigInteger::of($low)->multipliedBy(self::ISSUANCE_UNIT);
    }

    /** @param list<BigRational> $monthlyCfads */
    private function passesMonthly(LoanSchedule $schedule, array $monthlyCfads): bool
    {
        foreach ($monthlyCfads as $index => $monthly) {
            if ($monthly->dividedBy($schedule->instalments[$index])->isLessThan(self::REPEAT_MONTHLY_COVERAGE)) {
                return false;
            }
        }

        return true;
    }
}
