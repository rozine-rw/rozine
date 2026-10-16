<?php

declare(strict_types=1);

namespace App\Domain\Secondary;

use Brick\Math\BigInteger;

/** Whole units and whole francs as the market accepts them: positive integers with no sign, fraction or leading zero. */
final class Quantity
{
    public static function units(string $value): BigInteger
    {
        return self::positive($value, 'INVALID_UNITS');
    }

    public static function francs(string $value): BigInteger
    {
        return self::positive($value, 'INVALID_PRICE');
    }

    private static function positive(string $value, string $code): BigInteger
    {
        if (preg_match('/^[1-9][0-9]{0,18}$/D', $value) !== 1) {
            throw new SecondaryViolation($code);
        }

        return BigInteger::of($value);
    }
}
