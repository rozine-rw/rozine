<?php

declare(strict_types=1);

namespace App\Domain\Auditor;

use App\Domain\Underwriting\ExactFinancialValue;
use App\Domain\Underwriting\UnderwritingViolation;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

final class AuditVariance
{
    /** The sealed findings measured in francs. */
    private const array MONEY = ['stock_value', 'cash'];

    /** @return array{pct: string, within: bool}|null */
    public function compare(?string $reported, mixed $observed): ?array
    {
        if ($reported === null || ! is_string($observed)) {
            return null;
        }
        $baseline = ExactFinancialValue::amount($reported);
        try {
            $actual = ExactFinancialValue::amount($observed);
        } catch (UnderwritingViolation) {
            return null;
        }
        $difference = $actual->minus($baseline);
        if ($baseline->isZero()) {
            return $difference->isZero() ? ['pct' => '0.0', 'within' => true] : null;
        }

        return ['pct' => (string) $difference->toBigRational()->multipliedBy(100)->dividedBy($baseline)->toScale(1, RoundingMode::HalfUp),
            'within' => $difference->isZero()];
    }

    /**
     * Whether any sealed money finding differs at the published zero tolerance, before rounding.
     * A zero reported amount can still differ even though its percentage is undefined.
     *
     * @param  array<int, array<string, mixed>>  $findings
     */
    public static function hasMoneyDifference(array $findings): bool
    {
        foreach ($findings as $finding) {
            if (in_array($finding['measure'] ?? null, self::MONEY, true) && is_string($finding['reported'] ?? null) && is_string($finding['observed'] ?? null)
                && ! BigDecimal::of($finding['reported'])->isEqualTo(BigDecimal::of($finding['observed']))) {
                return true;
            }
        }

        return false;
    }

    /**
     * The weighted variance of a sealed report's money findings: the sum of absolute differences
     * over the sum of absolute reported amounts (a reported overdraft weighs as much as a balance),
     * as a percentage rounded half up to one decimal; null with nothing reported to weigh.
     *
     * @param  array<int, array<string, mixed>>  $findings  the sealed `report.findings`
     */
    public static function weighted(array $findings): ?string
    {
        $reported = $difference = BigDecimal::zero();
        foreach ($findings as $finding) {
            if (in_array($finding['measure'] ?? null, self::MONEY, true) && is_string($finding['reported'] ?? null) && is_string($finding['observed'] ?? null)) {
                $reported = $reported->plus(BigDecimal::of($finding['reported'])->abs());
                $difference = $difference->plus(BigDecimal::of($finding['observed'])->minus(BigDecimal::of($finding['reported']))->abs());
            }
        }

        return $reported->isZero() ? null : (string) $difference->multipliedBy(100)->dividedBy($reported, 1, RoundingMode::HalfUp);
    }
}
