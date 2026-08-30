<?php

namespace App\Domain\Pulse;

/**
 * Pure calculations for the non-binding Pulse demand simulation.
 *
 * The caller supplies the current year so this domain service has no clock or
 * framework dependency. Nothing here underwrites or approves a real loan.
 */
final class PulseUnderwriting
{
    /**
     * The blended yield quoted to investors, as a percentage.
     */
    public const BLENDED_YIELD = 13.0;

    /**
     * Every term the platform accepts. The marketing site offers 3-6 months and
     * the Pulse app offers 3/6/9/12, so the union is accepted server-side.
     *
     * @var list<int>
     */
    public const TERMS = [3, 4, 5, 6, 9, 12];

    public const MINIMUM_REVENUE = 15_000_000;

    public const EARLIEST_REGISTRATION_YEAR = 1996;

    public const MIN_LOAN = 3_000_000;

    public const MAX_LOAN = 100_000_000;

    public const PLEDGE_MINIMUM = 5_000;

    public const PLEDGE_MAXIMUM = 200_000_000;

    public const PLEDGE_STEP = 5_000;

    public const PLEDGE_DEFAULT = 500_000;

    private const COVER = 1.25;

    private const REVENUE_CEILING = 0.35;

    private const ROUNDING_STEP = 100_000;

    private const TRADING_YEARS_CAP = 10;

    private const MARGIN_CAP = 0.45;

    public static function monthlySurplus(int $annualRevenue, int $annualCosts): float
    {
        return ($annualRevenue - $annualCosts) / 12;
    }

    public static function profitMargin(int $annualRevenue, int $annualCosts): float
    {
        if ($annualRevenue <= 0 || $annualCosts >= $annualRevenue) {
            return 0;
        }

        return ($annualRevenue - $annualCosts) / $annualRevenue;
    }

    public static function yearsTrading(int $registeredYear, int $currentYear): int
    {
        return max(0, $currentYear - $registeredYear);
    }

    public static function score(
        int $annualRevenue,
        int $annualCosts,
        PulseSector $sector,
        int $registeredYear,
        int $currentYear,
    ): float {
        $score = 50
            + min(self::yearsTrading($registeredYear, $currentYear), self::TRADING_YEARS_CAP) * 1.5
            + $sector->score()
            + min(self::profitMargin($annualRevenue, $annualCosts), self::MARGIN_CAP) * 42;

        return max(40, min(92, $score));
    }

    /** @return array{band: string, score: float} */
    public static function rating(float $score): array
    {
        $outOfFive = round($score / 20, 1);

        $band = match (true) {
            $outOfFive >= 4 => 'Strong',
            $outOfFive >= 3 => 'Stable',
            $outOfFive >= 2 => 'Weak',
            default => 'Distressed',
        };

        return ['band' => $band, 'score' => $outOfFive];
    }

    public static function flatRate(int $termMonths, float $score): float
    {
        $rate = 10 + (100 - $score) * 0.085 + ($termMonths - 3) / 9 * 2.5;

        return max(10.5, min(15, $rate));
    }

    public static function affordablePayment(int $annualRevenue, int $annualCosts): float
    {
        return max(0, self::monthlySurplus($annualRevenue, $annualCosts)) / self::COVER;
    }

    public static function sizedAmount(int $annualRevenue, int $annualCosts, float $score, int $termMonths): int
    {
        $affordable = self::affordablePayment($annualRevenue, $annualCosts)
            * $termMonths
            / (1 + self::flatRate($termMonths, $score) / 100);

        $sized = min($affordable, $annualRevenue * self::REVENUE_CEILING);

        return (int) (floor(max(0, $sized) / self::ROUNDING_STEP) * self::ROUNDING_STEP);
    }

    public static function qualifiedAmount(int $annualRevenue, int $annualCosts, float $score, int $termMonths): int
    {
        return min(self::sizedAmount($annualRevenue, $annualCosts, $score, $termMonths), self::MAX_LOAN);
    }

    public static function surplusForMinimumLoan(float $score, int $termMonths): float
    {
        return self::MIN_LOAN * self::COVER * (1 + self::flatRate($termMonths, $score) / 100) / $termMonths;
    }

    /** @return array{total: float, monthly: float} */
    public static function repayment(int $qualifiedAmount, float $flatRate, int $termMonths): array
    {
        $total = $qualifiedAmount * (1 + $flatRate / 100);

        return ['total' => $total, 'monthly' => $total / $termMonths];
    }

    public static function coverRatio(int $annualRevenue, int $annualCosts, float $monthlyRepayment): float
    {
        if ($monthlyRepayment <= 0) {
            return 0;
        }

        return self::monthlySurplus($annualRevenue, $annualCosts) / $monthlyRepayment;
    }

    /**
     * Size the non-binding Pulse business simulation.
     *
     * @return array{
     *     annual_revenue: int,
     *     annual_costs: int,
     *     sector: PulseSector,
     *     registered_year: int,
     *     score: float,
     *     qualified_amount: int,
     *     term_months: int,
     *     flat_rate: float,
     *     rating_band: string,
     *     rating_score: float,
     *     rating: array{band: string, score: float},
     *     sized_amount: int,
     *     monthly_repayment: float,
     *     monthly_surplus: float,
     *     cover_ratio: float,
     *     below_minimum: bool,
     *     at_maximum: bool,
     *     required_surplus: float,
     *     status: 'pre_qualified'|'waitlisted'
     * }
     */
    public static function size(
        int $annualRevenue,
        int $annualCosts,
        PulseSector $sector,
        int $registeredYear,
        int $termMonths,
        int $currentYear,
    ): array {
        $score = self::score($annualRevenue, $annualCosts, $sector, $registeredYear, $currentYear);
        $rating = self::rating($score);
        $flatRate = self::flatRate($termMonths, $score);
        $sizedAmount = self::sizedAmount($annualRevenue, $annualCosts, $score, $termMonths);
        $qualifiedAmount = min($sizedAmount, self::MAX_LOAN);
        $monthlyRepayment = self::repayment($qualifiedAmount, $flatRate, $termMonths)['monthly'];
        $belowMinimum = $sizedAmount < self::MIN_LOAN;

        return [
            'annual_revenue' => $annualRevenue,
            'annual_costs' => $annualCosts,
            'sector' => $sector,
            'registered_year' => $registeredYear,
            'score' => $score,
            'qualified_amount' => $qualifiedAmount,
            'term_months' => $termMonths,
            'flat_rate' => round($flatRate, 2),
            'rating_band' => $rating['band'],
            'rating_score' => $rating['score'],
            'rating' => $rating,
            'sized_amount' => $sizedAmount,
            'monthly_repayment' => round($monthlyRepayment, 2),
            'monthly_surplus' => round(self::monthlySurplus($annualRevenue, $annualCosts), 2),
            'cover_ratio' => round(self::coverRatio($annualRevenue, $annualCosts, $monthlyRepayment), 2),
            'below_minimum' => $belowMinimum,
            'at_maximum' => $sizedAmount > self::MAX_LOAN,
            'required_surplus' => round(self::surplusForMinimumLoan($score, $termMonths), 2),
            'status' => $belowMinimum ? 'waitlisted' : 'pre_qualified',
        ];
    }

    public static function normalisePledge(int $pledgeAmount): int
    {
        $held = min(max($pledgeAmount, self::PLEDGE_MINIMUM), self::PLEDGE_MAXIMUM);

        return (int) round($held / self::PLEDGE_STEP) * self::PLEDGE_STEP;
    }

    public static function projectedReturn(int $pledgeAmount): int
    {
        return self::projectedReturnAtRate($pledgeAmount, self::BLENDED_YIELD);
    }

    public static function projectedReturnAtRate(int $pledgeAmount, float $rate): int
    {
        return (int) round($pledgeAmount * (1 + $rate / 100));
    }

    /** @return list<int> */
    public static function registrationYears(int $currentYear): array
    {
        return range($currentYear, self::EARLIEST_REGISTRATION_YEAR);
    }

    /**
     * @return array{
     *     terms: list<int>,
     *     sectors: list<string>,
     *     registration_years: list<int>,
     *     minimum_revenue: int,
     *     minimum_loan: int,
     *     maximum_loan: int,
     *     pledge: array{minimum: int, maximum: int, step: int, default: int}
     * }
     */
    public static function policy(int $currentYear): array
    {
        return [
            'terms' => self::TERMS,
            'sectors' => array_map(
                static fn (PulseSector $sector): string => $sector->value,
                PulseSector::cases(),
            ),
            'registration_years' => self::registrationYears($currentYear),
            'minimum_revenue' => self::MINIMUM_REVENUE,
            'minimum_loan' => self::MIN_LOAN,
            'maximum_loan' => self::MAX_LOAN,
            'pledge' => [
                'minimum' => self::PLEDGE_MINIMUM,
                'maximum' => self::PLEDGE_MAXIMUM,
                'step' => self::PLEDGE_STEP,
                'default' => self::PLEDGE_DEFAULT,
            ],
        ];
    }
}
