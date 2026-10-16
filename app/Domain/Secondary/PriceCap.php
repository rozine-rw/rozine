<?php

declare(strict_types=1);

namespace App\Domain\Secondary;

use Brick\Math\BigInteger;
use Brick\Math\RoundingMode;

/**
 * The highest price per unit a seller may ask (SEC-R03): what the units will still pay out under
 * the note's contract, per unit, rounded down to a whole franc. Rozine sets no minimum or
 * reference price, so any whole franc from 1 up to the cap is a valid ask.
 */
final class PriceCap
{
    /**
     * @param  list<array{principal: string, return: string}>  $remaining  the lot's instalments not yet paid, gross of every fee
     */
    public static function perUnit(array $remaining, string $units): BigInteger
    {
        $count = Quantity::units($units);
        $total = array_reduce($remaining, fn (BigInteger $sum, array $instalment): BigInteger => $sum->plus($instalment['principal'])->plus($instalment['return']),
            BigInteger::zero());

        return $total->dividedBy($count, RoundingMode::Down);
    }

    public static function requireWithin(string $price, BigInteger $cap): BigInteger
    {
        $ask = Quantity::francs($price);
        if ($ask->isGreaterThan($cap)) {
            throw new SecondaryViolation('PRICE_ABOVE_CAP');
        }

        return $ask;
    }
}
