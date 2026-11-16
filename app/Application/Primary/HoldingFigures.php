<?php

declare(strict_types=1);

namespace App\Application\Primary;

use App\Application\Primary\Contracts\HoldingPayouts;
use App\Application\Primary\Contracts\InvestorHoldings;
use Brick\Math\BigInteger;
use Brick\Math\RoundingMode;
use Carbon\CarbonImmutable;

/**
 * The figures an Investor sees for issued Holdings, from the issue and what servicing has paid on
 * to each so far (`HoldingPayouts`). A Holding is valued at what was invested plus the gross return
 * received, and every scheduled instalment still unpaid and due today or later is a projection. An instalment reaches the Investor net of
 * the earnings fee locked at commitment, rounded half up per instalment as the checkout quoted it.
 *
 * @phpstan-import-type IssuedHoldingRecord from InvestorHoldings
 * @phpstan-import-type Money from InvestorHoldings
 * @phpstan-import-type HoldingPayout from HoldingPayouts
 *
 * @phpstan-type Card array{name: string, accent: string, industry: string, district: string, rating: array{band: string, score: string}, investors: int}
 * @phpstan-type HeldPosition array{holding: IssuedHoldingRecord, card: Card, paid: list<HoldingPayout>}
 * @phpstan-type Projectable array{holding: array{schedule: list<array{index: int, due_on: string, principal: Money, return: Money}>, terms: array{earnings_fee: array{rate_bps: int}}},
 *     card: array{name: string, accent: string}, paid: list<HoldingPayout>}
 * @phpstan-type Received array{made: int, principal: BigInteger, return: BigInteger, net: BigInteger}
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
     * The Holding as its units still held carry it, once some were sold on the market: its
     * principal, schedule, total return and payout fee scaled to the units left (rounded down),
     * the issue's own record left as it was recorded.
     *
     * @param  IssuedHoldingRecord  $holding
     * @return IssuedHoldingRecord
     */
    public static function held(array $holding, int $resold): array
    {
        if ($resold === 0) {
            return $holding;
        }
        $units = (int) $holding['units'];
        $left = $units - $resold;
        $scale = fn (string $amount): array => self::money(BigInteger::of($amount)->multipliedBy($left)->dividedBy($units, RoundingMode::Down));

        return [...$holding, 'principal' => $scale($holding['principal']['amount']),
            'schedule' => array_map(fn (array $entry): array => [...$entry, 'principal' => $scale($entry['principal']['amount']),
                'return' => $scale($entry['return']['amount'])], $holding['schedule']),
            'rights' => [...$holding['rights'], 'total_return' => $scale($holding['rights']['total_return']['amount'])],
            'terms' => [...$holding['terms'], 'payout_fee' => $scale($holding['terms']['payout_fee']['amount'])]];
    }

    /**
     * The scheduled instalments not yet paid and due today or later in Kigali, earliest first, each net.
     *
     * @param  list<Projectable>  $positions
     * @return list<Projected>
     */
    public static function upcoming(array $positions, CarbonImmutable $now): array
    {
        $today = $now->setTimezone(self::ZONE)->format('Y-m-d');
        $projected = [];
        foreach ($positions as $position) {
            $paid = self::paidInstalments($position['paid']);
            foreach ($position['holding']['schedule'] as $entry) {
                if ($entry['due_on'] >= $today && ! in_array($entry['index'], $paid, true)) {
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

    /**
     * What servicing has paid on a Holding so far: the instalments paid, and the gross principal,
     * gross return and net that reached the wallet.
     *
     * @param  list<HoldingPayout>  $payouts
     * @return Received
     */
    public static function received(array $payouts): array
    {
        $principal = $return = $net = BigInteger::zero();
        foreach ($payouts as $payout) {
            $principal = $principal->plus($payout['principal']);
            $return = $return->plus($payout['return']);
            $net = $net->plus($payout['net']);
        }

        return ['made' => count(self::paidInstalments($payouts)), 'principal' => $principal, 'return' => $return, 'net' => $net];
    }

    /**
     * @param  list<HoldingPayout>  $payouts
     * @return list<int>
     */
    public static function paidInstalments(array $payouts): array
    {
        return array_values(array_unique(array_merge([], ...array_map(fn (array $payout): array => $payout['instalments'], $payouts))));
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
