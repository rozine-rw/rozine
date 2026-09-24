<?php

declare(strict_types=1);

namespace App\Domain\Underwriting;

use Brick\Math\BigInteger;
use Brick\Math\BigRational;

/** Arithmetic eligibility; the application must still authorize and lock current evidence/exposure. */
final class LoanCapacity
{
    public const CALCULATOR_VERSION = 'underwriting-1';

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
        if ($initialDscr->isLessThan('5/4')) {
            return $result;
        }

        $rawCapacity = $cfads->dividedBy('3/2')->multipliedBy($tenor)->dividedBy($rate->plus(1));
        $result['raw_capacity'] = ExactFinancialValue::ratio($rawCapacity);
        if (BigRational::min($rawCapacity, $remainingRoom, $requestedPrincipal)->isLessThan(3000000)) {
            $result['code'] = 'CAPACITY_BELOW_MINIMUM';

            return $result;
        }

        $candidate = BigInteger::min(
            ExactFinancialValue::halfUp($rawCapacity), $requestedPrincipal, 100000000,
            ExactFinancialValue::floor($remainingRoom),
        );
        $result['rounded_candidate'] = ExactFinancialValue::money($candidate);
        $schedule = LoanSchedule::build((string) $candidate, $tenor, $rate);
        while ($schedule->dscr($cfads)->isLessThan('3/2')) {
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
        if ($remainingRoom->isLessThan(BigRational::min($rawCapacity, $requestedPrincipal, 100000000))) {
            $reasons[] = 'EXPOSURE_CAPPED';
        }
        if ($original->principal->isGreaterThan(100000000)) {
            $reasons[] = 'PRINCIPAL_MAX_CAPPED';
        }
        if (! $guard->isZero()) {
            $reasons[] = 'ROUNDING_GUARD';
        }
        $quantized = $candidate->quotient(5000)->multipliedBy(5000);
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
        if ($quantized->isLessThan(3000000)) {
            $result['code'] = 'CAPACITY_BELOW_MINIMUM';

            return $result;
        }

        if ($acceptedPrincipal !== null) {
            $accepted = ExactFinancialValue::amount($acceptedPrincipal);
            if ($accepted->isLessThan(3000000) || $accepted->isGreaterThan($quantized) || ! $accepted->remainder(5000)->isZero()) {
                throw new UnderwritingViolation('INVALID_ACCEPTED_PRINCIPAL');
            }
            $quantized = $accepted;
        }
        $final = LoanSchedule::build((string) $quantized, $tenor, $rate);
        $result['code'] = 'UNDERWRITING_ELIGIBLE';
        $result['eligible'] = true;
        $result['offer'] = [...$final->toArray(), 'units' => (string) $quantized->quotient(5000)];
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
        if (! $this->passesMonthly(LoanSchedule::build('3000000', $tenor, $rate), $monthlyCfads)) {
            return BigInteger::zero();
        }
        $low = 600;
        $high = $maximum->quotient(5000)->toInt();
        while ($low < $high) {
            $middle = intdiv($low + $high + 1, 2);
            $schedule = LoanSchedule::build((string) ($middle * 5000), $tenor, $rate);
            if ($this->passesMonthly($schedule, $monthlyCfads)) {
                $low = $middle;
            } else {
                $high = $middle - 1;
            }
        }

        return BigInteger::of($low)->multipliedBy(5000);
    }

    /** @param list<BigRational> $monthlyCfads */
    private function passesMonthly(LoanSchedule $schedule, array $monthlyCfads): bool
    {
        foreach ($monthlyCfads as $index => $monthly) {
            if ($monthly->dividedBy($schedule->instalments[$index])->isLessThan('27/20')) {
                return false;
            }
        }

        return true;
    }
}
