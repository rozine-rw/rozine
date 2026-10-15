<?php

declare(strict_types=1);

namespace App\Domain\Primary;

use Brick\Math\BigInteger;

/**
 * The Rozine Plus earnings-fee bands (BRS v2.0 INV-R04). The band follows active capital after the
 * checkout: principal outstanding in held notes plus the checkout total, never wallet cash. It is
 * evaluated once at checkout and every line of it locks that rate for the life of the investment.
 */
final class EarningsFeeBand
{
    public const string POLICY_VERSION = 'brs-2.0-inv-r04';

    /** Rate in basis points by tier, from the lowest band of active capital to the highest. */
    public const array RATES = ['standard' => 1500, 'bronze' => 1200, 'silver' => 1000, 'gold' => 900, 'platinum' => 800];

    /** The lower bound of each tier above `standard` in RWF, highest first; a tier runs up to, not including, the next bound. */
    private const array FLOORS = ['platinum' => '100000000', 'gold' => '25000000', 'silver' => '5000000', 'bronze' => '1000000'];

    /** @return array{tier: string, rate_bps: int, basis: string, policy_version: string} */
    public static function forActiveCapital(BigInteger $activeCapital): array
    {
        if ($activeCapital->isNegative()) {
            throw new PrimaryViolation('INVALID_MONEY');
        }
        $tier = array_find_key(self::FLOORS, fn (string $floor): bool => $activeCapital->isGreaterThanOrEqualTo($floor)) ?? 'standard';

        return ['tier' => $tier, 'rate_bps' => self::RATES[$tier], 'basis' => 'return_only', 'policy_version' => self::POLICY_VERSION];
    }
}
