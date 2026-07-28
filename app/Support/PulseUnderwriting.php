<?php

namespace App\Support;

/**
 * The demand-simulation model behind Pulse.
 *
 * These are the same formulas the waitlist runs client side, kept here so the
 * figures that get persisted are derived server side rather than trusted from
 * the browser. Nothing here underwrites a real loan.
 */
class PulseUnderwriting
{
    /**
     * The strength score assigned to a business that uploads a statement.
     */
    public const DEFAULT_SCORE = 68;

    /**
     * The blended yield quoted to investors, as a percentage.
     */
    public const BLENDED_YIELD = 12.5;

    /**
     * The multiple of annual inflow a business may borrow against.
     */
    private const CAPACITY_MULTIPLE = 2.75;

    /**
     * The terms, in months, a business may pick from.
     *
     * @var list<int>
     */
    public const TERMS = [3, 6, 9, 12];

    /**
     * Translate a strength score into a rating band and a score out of five.
     *
     * @return array{band: string, score: float}
     */
    public static function rating(int $score = self::DEFAULT_SCORE): array
    {
        $outOfFive = round($score / 20 * 10) / 10;

        $band = match (true) {
            $outOfFive >= 4 => 'Strong',
            $outOfFive >= 3 => 'Stable',
            $outOfFive >= 2 => 'Weak',
            default => 'Distressed',
        };

        return ['band' => $band, 'score' => $outOfFive];
    }

    /**
     * Get the flat rate, as a percentage, for a score over a given term.
     */
    public static function flatRate(int $termMonths, int $score = self::DEFAULT_SCORE): float
    {
        $rate = 10 + (100 - $score) * 0.08 + ($termMonths - 3) / 9 * 1.5;

        return round(max(10, min(15, $rate)), 1);
    }

    /**
     * Get the amount a business pre-qualifies for over a given term.
     */
    public static function qualifiedAmount(int $annualInflow, int $termMonths, int $score = self::DEFAULT_SCORE): int
    {
        $capacity = self::CAPACITY_MULTIPLE * $annualInflow;
        $rate = self::flatRate($termMonths, $score);

        return (int) round($capacity * $termMonths / (24 * (1 + $rate / 100)));
    }

    /**
     * Size a business' pre-qualification against a year of cash flow.
     *
     * @return array{annual_inflow: int, qualified_amount: int, term_months: int, flat_rate: float, rating_band: string, rating_score: float}
     */
    public static function size(int $annualInflow, int $termMonths): array
    {
        $rating = self::rating();

        return [
            'annual_inflow' => $annualInflow,
            'qualified_amount' => self::qualifiedAmount($annualInflow, $termMonths),
            'term_months' => $termMonths,
            'flat_rate' => self::flatRate($termMonths),
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

    /**
     * Estimate the average annual inflow read off an uploaded statement.
     */
    public static function estimateAnnualInflow(): int
    {
        return random_int(48, 127) * 1_000_000;
    }
}
