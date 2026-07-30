<?php

namespace App\Support;

use App\Enums\PulseSector;
use Illuminate\Support\Carbon;

/**
 * The demand-simulation model behind Pulse.
 *
 * A business sizes itself on five figures it reports: revenue and costs over
 * the last twelve months, what it does, the year it registered and the term it
 * wants. These are the same formulas the waitlist runs client side, kept here
 * so the figures that get persisted are derived server side rather than trusted
 * from the browser. Nothing here underwrites a real loan.
 */
class PulseUnderwriting
{
    /**
     * The blended yield quoted to investors, as a percentage.
     */
    public const BLENDED_YIELD = 13.0;

    /**
     * The terms, in months, a business may pick from.
     *
     * @var list<int>
     */
    public const TERMS = [3, 6, 9, 12];

    /**
     * The smallest revenue a business can be sized on.
     */
    public const MINIMUM_REVENUE = 15_000_000;

    /**
     * The earliest year of registration the waitlist offers.
     */
    public const EARLIEST_REGISTRATION_YEAR = 1996;

    /**
     * The smallest loan Rozine writes.
     */
    public const MIN_LOAN = 5_000_000;

    /**
     * The largest loan Rozine writes.
     */
    public const MAX_LOAN = 50_000_000;

    /**
     * The cover a monthly repayment must leave on top of itself, so a business
     * never commits every franc of its surplus.
     */
    private const COVER = 1.25;

    /**
     * The share of a year's revenue a loan may never exceed.
     */
    private const REVENUE_CEILING = 0.35;

    /**
     * The step a pre-qualified amount is rounded down to.
     */
    private const ROUNDING_STEP = 100_000;

    /**
     * The years of trading beyond which more history adds nothing.
     */
    private const TRADING_YEARS_CAP = 10;

    /**
     * The profit margin beyond which a fatter margin adds nothing.
     */
    private const MARGIN_CAP = 0.45;

    /**
     * Get the money a business has left each month to service debt with.
     */
    public static function monthlySurplus(int $annualRevenue, int $annualCosts): float
    {
        return ($annualRevenue - $annualCosts) / 12;
    }

    /**
     * Get the share of revenue a business keeps.
     */
    public static function profitMargin(int $annualRevenue, int $annualCosts): float
    {
        if ($annualRevenue <= 0 || $annualCosts >= $annualRevenue) {
            return 0;
        }

        return ($annualRevenue - $annualCosts) / $annualRevenue;
    }

    /**
     * Get the years a business has been trading.
     */
    public static function yearsTrading(int $registeredYear): int
    {
        return max(0, Carbon::now()->year - $registeredYear);
    }

    /**
     * Score a business' strength out of a hundred, held between forty and
     * ninety-two so neither a long history nor a fat margin runs away with it.
     */
    public static function score(int $annualRevenue, int $annualCosts, PulseSector $sector, int $registeredYear): float
    {
        $score = 50
            + min(self::yearsTrading($registeredYear), self::TRADING_YEARS_CAP) * 1.5
            + $sector->score()
            + min(self::profitMargin($annualRevenue, $annualCosts), self::MARGIN_CAP) * 42;

        return max(40, min(92, $score));
    }

    /**
     * Translate a strength score into a rating band and a score out of five.
     *
     * @return array{band: string, score: float}
     */
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

    /**
     * Get the flat rate, as a percentage, for a score over a given term. A
     * weaker score and a longer term both raise it.
     */
    public static function flatRate(int $termMonths, float $score): float
    {
        $rate = 10 + (100 - $score) * 0.085 + ($termMonths - 3) / 9 * 2.5;

        return max(10.5, min(15, $rate));
    }

    /**
     * Get the repayment a business could carry each month, which is its surplus
     * less the cover the model insists on.
     */
    public static function affordablePayment(int $annualRevenue, int $annualCosts): float
    {
        return max(0, self::monthlySurplus($annualRevenue, $annualCosts)) / self::COVER;
    }

    /**
     * Get the amount a business' figures size to, before the loan band applies.
     *
     * Affordability binds first; the share-of-revenue ceiling is the backstop.
     */
    public static function sizedAmount(int $annualRevenue, int $annualCosts, float $score, int $termMonths): int
    {
        $affordable = self::affordablePayment($annualRevenue, $annualCosts)
            * $termMonths
            / (1 + self::flatRate($termMonths, $score) / 100);

        $sized = min($affordable, $annualRevenue * self::REVENUE_CEILING);

        return (int) (floor(max(0, $sized) / self::ROUNDING_STEP) * self::ROUNDING_STEP);
    }

    /**
     * Get the amount a business pre-qualifies for, held to the loan band Rozine
     * writes within. A business that sizes under the floor is still recorded at
     * what it sized to; it joins the waitlist rather than qualifying.
     */
    public static function qualifiedAmount(int $annualRevenue, int $annualCosts, float $score, int $termMonths): int
    {
        return min(self::sizedAmount($annualRevenue, $annualCosts, $score, $termMonths), self::MAX_LOAN);
    }

    /**
     * Get the monthly surplus the smallest loan Rozine writes would need over a
     * given term, which is what a business short of it has to close.
     */
    public static function surplusForMinimumLoan(float $score, int $termMonths): float
    {
        return self::MIN_LOAN * self::COVER * (1 + self::flatRate($termMonths, $score) / 100) / $termMonths;
    }

    /**
     * Get what a pre-qualified amount costs to repay.
     *
     * @return array{total: float, monthly: float}
     */
    public static function repayment(int $qualifiedAmount, float $flatRate, int $termMonths): array
    {
        $total = $qualifiedAmount * (1 + $flatRate / 100);

        return ['total' => $total, 'monthly' => $total / $termMonths];
    }

    /**
     * Get the cover a repayment leaves on top of itself.
     */
    public static function coverRatio(int $annualRevenue, int $annualCosts, float $monthlyRepayment): float
    {
        if ($monthlyRepayment <= 0) {
            return 0;
        }

        return self::monthlySurplus($annualRevenue, $annualCosts) / $monthlyRepayment;
    }

    /**
     * Size a business' pre-qualification against the figures it reported.
     *
     * @return array{annual_revenue: int, annual_costs: int, sector: PulseSector, registered_year: int, score: float, qualified_amount: int, term_months: int, flat_rate: float, rating_band: string, rating_score: float}
     */
    public static function size(int $annualRevenue, int $annualCosts, PulseSector $sector, int $registeredYear, int $termMonths): array
    {
        $score = self::score($annualRevenue, $annualCosts, $sector, $registeredYear);
        $rating = self::rating($score);

        return [
            'annual_revenue' => $annualRevenue,
            'annual_costs' => $annualCosts,
            'sector' => $sector,
            'registered_year' => $registeredYear,
            'score' => $score,
            'qualified_amount' => self::qualifiedAmount($annualRevenue, $annualCosts, $score, $termMonths),
            'term_months' => $termMonths,
            'flat_rate' => round(self::flatRate($termMonths, $score), 2),
            'rating_band' => $rating['band'],
            'rating_score' => $rating['score'],
        ];
    }

    /**
     * Get the amount an investor is projected to receive back.
     */
    public static function projectedReturn(int $pledgeAmount): int
    {
        return (int) round($pledgeAmount * (1 + self::BLENDED_YIELD / 100));
    }
}
