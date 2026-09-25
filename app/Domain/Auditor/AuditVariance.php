<?php

declare(strict_types=1);

namespace App\Domain\Auditor;

use App\Domain\Underwriting\ExactFinancialValue;
use App\Domain\Underwriting\UnderwritingViolation;
use Brick\Math\RoundingMode;

final class AuditVariance
{
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
}
