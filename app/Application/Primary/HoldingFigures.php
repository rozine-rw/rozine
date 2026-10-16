<?php

declare(strict_types=1);

namespace App\Application\Primary;

use App\Application\Primary\Contracts\InvestorHoldings;
use Brick\Math\BigInteger;
use Brick\Math\RoundingMode;
use Carbon\CarbonImmutable;

/**
 * The figures an Investor sees for issued Holdings, from the issue alone. Nothing is serviced yet,
 * so nothing has been received: every Holding is valued at what was invested, with no gain, and
 * every scheduled instalment still due is a projection. An instalment reaches the Investor net of
 * the earnings fee locked at commitment, rounded half up per instalment as the checkout quoted it.
 *
 * @phpstan-import-type IssuedHoldingRecord from InvestorHoldings
 * @phpstan-import-type Money from InvestorHoldings
 *
 * @phpstan-type Card array{name: string, accent: string, industry: string, district: string, rating: array{band: string, score: string}, investors: int}
 * @phpstan-type HeldPosition array{holding: IssuedHoldingRecord, card: Card}
 * @phpstan-type Projected array{due_on: string, amount: Money, name: string, accent: string}
 */
final class HoldingFigures
{
    public const string ZONE = 'Africa/Kigali';

    /** A business holding at least this share of invested capital makes the portfolio concentrated (Investor.dc.html). */
    public const int CONCENTRATED_PCT = 30;

    /**
     * What one instalment pays the Investor: its principal and return, less the earnings fee on
     * the return.
     *
     * @param  array{principal: Money, return: Money}  $instalment
     * @return Money
     */
    public static function net(array $instalment, int $feeBps): array
    {
        $return = BigInteger::of($instalment['return']['amount']);
        $fee = $return->multipliedBy($feeBps)->dividedBy(10000, RoundingMode::HalfUp);

        return self::money(BigInteger::of($instalment['principal']['amount'])->plus($return)->minus($fee));
    }

    /**
     * Principal plus total return, less the payout fee quoted at commitment.
     *
     * @param  IssuedHoldingRecord  $holding
     * @return Money
     */
    public static function maturityValue(array $holding): array
    {
        return self::money(BigInteger::of($holding['principal']['amount'])->plus($holding['rights']['total_return']['amount'])
            ->minus($holding['terms']['payout_fee']['amount']));
    }

    /**
     * The scheduled instalments due today or later in Kigali, earliest first, each net.
     *
     * @param  list<HeldPosition>  $positions
     * @return list<Projected>
     */
    public static function upcoming(array $positions, CarbonImmutable $now): array
    {
        $today = $now->setTimezone(self::ZONE)->format('Y-m-d');
        $projected = [];
        foreach ($positions as $position) {
            foreach ($position['holding']['schedule'] as $entry) {
                if ($entry['due_on'] >= $today) {
                    $projected[] = ['due_on' => $entry['due_on'], 'amount' => self::net($entry, $position['holding']['terms']['earnings_fee']['rate_bps']),
                        'name' => $position['card']['name'], 'accent' => $position['card']['accent']];
                }
            }
        }
        usort($projected, fn (array $left, array $right): int => [$left['due_on'], $left['name']] <=> [$right['due_on'], $right['name']]);

        return $projected;
    }

    /** A Kigali calendar date as that day's Kigali midnight. */
    public static function kigaliMidnight(string $date): string
    {
        return CarbonImmutable::parse($date, self::ZONE)->startOfDay()->toIso8601String();
    }

    /** Half-up whole percent of a part in a whole; zero for an empty whole. */
    public static function percent(BigInteger|string $part, BigInteger|string $whole): int
    {
        $whole = BigInteger::of($whole);

        return $whole->isZero() ? 0 : BigInteger::of($part)->multipliedBy(100)->dividedBy($whole, RoundingMode::HalfUp)->toInt();
    }

    /** @return Money */
    public static function money(BigInteger|string $amount): array
    {
        return ['currency' => 'RWF', 'amount' => (string) $amount];
    }
}
