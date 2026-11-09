<?php

declare(strict_types=1);

namespace App\Domain\Underwriting;

use Brick\Math\BigInteger;
use Brick\Math\BigRational;

/** Arithmetic eligibility; the application must still authorize and lock current evidence/exposure. */
final class LoanCapacity
{
    public const CALCULATOR_VERSION = 'underwriting-brs-v3-2026-11-09';

    /** The coverage every offer is sized to (DSCR 1.50). */
    public const string TARGET_COVERAGE = '3/2';

    /** A request below the single 1.50 threshold is rejected, never resized into approval. */
    public const string MINIMUM_COVERAGE = self::TARGET_COVERAGE;

    /** The smallest and largest principal one note may raise, in RWF. */
    public const int MINIMUM_PRINCIPAL = 3000000;

    public const int MAXIMUM_PRINCIPAL = 100000000;

    /** The same threshold applies to every evidenced repeat-month instalment. */
    public const string REPEAT_MONTHLY_COVERAGE = self::TARGET_COVERAGE;

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
        if ($initialDscr->isLessThan(self::MINIMUM_COVERAGE)
            || ! $this->passesMonthly($original, $repeatMonthlyCfads === [] ? array_fill(0, $tenor, $cfads) : $repeatMonthlyCfads)) {
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
        if (! $this->passesMonthly($final, $repeatMonthlyCfads === [] ? array_fill(0, $tenor, $cfads) : $repeatMonthlyCfads)) {
            return $result;
        }
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

    /**
     * Informational capacity for a new request, never a counteroffer on a rejected application.
     *
     * @param  list<BigRational>  $repeatMonthlyCfads
     * @return array<string, mixed>|null
     */
    public function plan(int $tenor, BigRational $rate, BigRational $cfads, BigRational $remainingRoom, array $repeatMonthlyCfads = []): ?array
    {
        LoanSchedule::build((string) self::MINIMUM_PRINCIPAL, $tenor, $rate);
        if ($repeatMonthlyCfads !== [] && count($repeatMonthlyCfads) !== $tenor) {
            throw new UnderwritingViolation('UNDERWRITING_EVIDENCE_REQUIRED');
        }
        $rawCapacity = $cfads->dividedBy(self::TARGET_COVERAGE)->multipliedBy($tenor)->dividedBy($rate->plus(1));
        $limit = BigRational::min($rawCapacity, $remainingRoom, self::MAXIMUM_PRINCIPAL);
        if ($limit->isLessThan(self::MINIMUM_PRINCIPAL)) {
            return null;
        }
        $minimum = $this->evaluate((string) self::MINIMUM_PRINCIPAL, $tenor, $rate, $cfads, $remainingRoom, $repeatMonthlyCfads);
        if (! $minimum['eligible']) {
            return null;
        }
        $low = intdiv(self::MINIMUM_PRINCIPAL, self::ISSUANCE_UNIT);
        $high = ExactFinancialValue::floor($limit)->quotient(self::ISSUANCE_UNIT)->toInt();
        while ($low < $high) {
            $middle = intdiv($low + $high + 1, 2);
            $result = $this->evaluate((string) ($middle * self::ISSUANCE_UNIT), $tenor, $rate, $cfads, $remainingRoom, $repeatMonthlyCfads);
            if ($result['eligible']) {
                $low = $middle;
            } else {
                $high = $middle - 1;
            }
        }

        return $this->evaluate((string) ($low * self::ISSUANCE_UNIT), $tenor, $rate, $cfads, $remainingRoom, $repeatMonthlyCfads)['offer'];
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
