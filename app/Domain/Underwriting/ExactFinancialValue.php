<?php

declare(strict_types=1);

namespace App\Domain\Underwriting;

use Brick\Math\BigInteger;
use Brick\Math\BigRational;
use Brick\Math\RoundingMode;

final class ExactFinancialValue
{
    public static function amount(string $amount): BigInteger
    {
        if (! preg_match('/^(0|[1-9][0-9]{0,63})$/D', $amount)) {
            throw new UnderwritingViolation('INVALID_MONEY');
        }

        return BigInteger::of($amount);
    }

    public static function halfUp(BigRational $value): BigInteger
    {
        return $value->toScale(0, RoundingMode::HalfUp)->toBigInteger();
    }

    public static function floor(BigRational $value): BigInteger
    {
        return $value->toScale(0, RoundingMode::Floor)->toBigInteger();
    }

    /** @return array{numerator: string, denominator: string} */
    public static function ratio(BigRational $value): array
    {
        return ['numerator' => (string) $value->getNumerator(), 'denominator' => (string) $value->getDenominator()];
    }

    /** @return array{currency: string, amount: string} */
    public static function money(BigInteger $value): array
    {
        return ['currency' => 'RWF', 'amount' => (string) $value];
    }

    /** @param non-empty-list<BigRational> $values */
    public static function median(array $values): BigRational
    {
        usort($values, fn (BigRational $left, BigRational $right): int => $left->compareTo($right));
        $middle = intdiv(count($values), 2);

        return count($values) % 2 === 1 ? $values[$middle] : $values[$middle - 1]->plus($values[$middle])->dividedBy(2);
    }
}
