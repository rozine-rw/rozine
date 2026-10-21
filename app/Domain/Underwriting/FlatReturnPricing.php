<?php

declare(strict_types=1);

namespace App\Domain\Underwriting;

use Brick\Math\BigRational;
use Brick\Math\RoundingMode;

final class FlatReturnPricing
{
    public const POLICY_VERSION = 'engineering-2026-09-23.4';

    /** The flat, all-in return is kept within this band, in percent. */
    public const int MINIMUM_RETURN_PERCENT = 10;

    public const int MAXIMUM_RETURN_PERCENT = 15;

    public static function validateTenor(int $tenor): void
    {
        if (! in_array($tenor, [3, 4, 5, 6], true)) {
            throw new UnderwritingViolation('INVALID_TENOR');
        }
    }

    /** @return array<string, mixed> */
    public function calculate(string $publishedRating, int $tenor): array
    {
        self::validateTenor($tenor);
        if (! preg_match('/^(?:[0-4]\.[0-9]|5\.0)$/D', $publishedRating)) {
            throw new UnderwritingViolation('PUBLISHED_RATING_REQUIRED');
        }
        $premium = BigRational::ofFraction($tenor - 3, 6);
        $raw = BigRational::of(5)->minus($publishedRating)->multipliedBy('8/5')->plus(10)->plus($premium);
        $clamped = BigRational::min(self::MAXIMUM_RETURN_PERCENT, BigRational::max(self::MINIMUM_RETURN_PERCENT, $raw));
        $percent = $clamped->toScale(1, RoundingMode::HalfUp);

        return [
            'policy_version' => self::POLICY_VERSION,
            'published_rating' => $publishedRating,
            'tenor_months' => $tenor,
            'premium_percentage_points' => ExactFinancialValue::ratio($premium),
            'raw_percent' => ExactFinancialValue::ratio($raw),
            'clamped_percent' => ExactFinancialValue::ratio($clamped),
            'percent' => (string) $percent,
            'fraction' => ExactFinancialValue::ratio($percent->toBigRational()->dividedBy(100)),
        ];
    }
}
