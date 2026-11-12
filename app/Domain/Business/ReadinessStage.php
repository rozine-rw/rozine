<?php

declare(strict_types=1);

namespace App\Domain\Business;

use Brick\Math\BigInteger;
use Brick\Math\RoundingMode;

/**
 * A Business's readiness stage (BRS v3.0 RDY-R01, RDY-R02, built to the proposed OD-21 thresholds).
 * VMR is the average verified revenue of the last 3 sealed monthly reports. Every reading is
 * replayed from the first 3 reports onward, so the stage needs no stored state: the first reading
 * sets it, and after that it moves only once 3 consecutive readings sit on the same side of it and
 * 2 of the last 3 months do too. One month above or below a threshold therefore never moves the
 * stage, in either direction. Stage 0 is
 * a business below Foundation; null is a business with fewer than 3 sealed reports. Readiness is
 * Business-only and never changes a label, a return or any note's terms.
 *
 * @phpstan-type Month array{period: string, revenue: string}
 * @phpstan-type Stage array{vmr: string|null, stage: int|null, indicated: int|null, months_pointing: int,
 *     reports: int, reports_needed: int, window: int, next: array{stage: int, threshold: string}|null,
 *     thresholds: list<array{stage: int, from: string}>, months: list<Month>}
 */
final class ReadinessStage
{
    public const string POLICY_VERSION = 'brs-3.0-od-21-proposed';

    /** Reports averaged into one VMR reading, and readings needed to move a stage. */
    public const int WINDOW = 3;

    /** The lower bound of each stage in whole RWF: 1 Foundation, 2 Growth, 3 Scale, 4 Market. */
    public const array THRESHOLDS = [1 => '2500000', 2 => '10000000', 3 => '30000000', 4 => '80000000'];

    /**
     * The months a VMR reads from published monthly reports, listed newest first by the port, oldest first here:
     * each period once, at its newest report, with its verified inflow as revenue.
     *
     * @param  list<array{period: string, inflow: string}>  $reports
     * @return list<Month>
     */
    public static function months(array $reports): array
    {
        $months = array_map(fn (array $report): array => ['period' => $report['period'], 'revenue' => $report['inflow']], $reports);

        return array_values(array_column(array_reverse($months), null, 'period'));
    }

    /**
     * @param  list<Month>  $reports  sealed monthly reports in any order, one per period
     * @return Stage
     */
    public function assess(array $reports): array
    {
        usort($reports, fn (array $left, array $right): int => strcmp($left['period'], $right['period']));
        $readings = [];
        $stage = null;
        $history = [];
        for ($last = self::WINDOW - 1; $last < count($reports); $last++) {
            $window = array_slice($reports, $last - self::WINDOW + 1, self::WINDOW);
            $readings[] = $this->average($window);
            $history[] = self::stageFor($readings[array_key_last($readings)]);
            $stage = $stage === null ? $history[0] : $this->settle($stage, array_slice($history, -self::WINDOW), $window);
        }
        $vmr = $readings === [] ? null : $readings[array_key_last($readings)];
        $indicated = $history === [] ? null : $history[array_key_last($history)];

        return ['vmr' => $vmr === null ? null : (string) $vmr, 'stage' => $stage, 'indicated' => $indicated,
            'months_pointing' => $stage === null ? 0 : $this->pointing($stage, $history),
            'reports' => count($reports), 'reports_needed' => max(0, self::WINDOW - count($reports)), 'window' => self::WINDOW,
            'next' => $stage === null || $stage === 4 ? null : ['stage' => $stage + 1, 'threshold' => self::THRESHOLDS[$stage + 1]],
            'thresholds' => array_map(fn (int $number, string $from): array => ['stage' => $number, 'from' => $from], array_keys(self::THRESHOLDS), self::THRESHOLDS),
            'months' => array_reverse(array_slice($reports, -self::WINDOW))];
    }

    /**
     * The stage one VMR reading points to on its own; 0 below Foundation.
     *
     * @return int<0, 4>
     */
    public static function stageFor(BigInteger $vmr): int
    {
        $stage = 0;
        foreach (self::THRESHOLDS as $candidate => $threshold) {
            if ($vmr->isGreaterThanOrEqualTo(BigInteger::of($threshold))) {
                $stage = $candidate;
            }
        }

        return $stage;
    }

    /** @param list<Month> $months */
    private function average(array $months): BigInteger
    {
        $total = BigInteger::zero();
        foreach ($months as $month) {
            $total = $total->plus(BigInteger::of($month['revenue']));
        }

        return $total->dividedBy(count($months), RoundingMode::Down);
    }

    /**
     * The stage after the newest reading: it moves only when the last 3 readings all sit on one
     * side of it and at least 2 of the last 3 months sit there too, and then only as far as the
     * nearest of the readings. A single strong month lifts every 3-month average it is part of, so
     * the months themselves are checked: one month beyond a threshold never moves the stage.
     *
     * @param  int<0, 4>  $stage
     * @param  list<int<0, 4>>  $recent
     * @param  list<Month>  $months
     * @return int<0, 4>
     */
    private function settle(int $stage, array $recent, array $months): int
    {
        if (count($recent) < self::WINDOW) {
            return $stage;
        }
        $monthly = array_map(fn (array $month): int => self::stageFor(BigInteger::of($month['revenue'])), $months);
        $target = min($recent) > $stage ? min($recent) : (max($recent) < $stage ? max($recent) : $stage);
        $agreeing = array_filter($monthly, fn (int $monthStage): bool => $target > $stage ? $monthStage >= $target : $monthStage <= $target);

        return count($agreeing) >= self::WINDOW - 1 ? $target : $stage;
    }

    /**
     * How many of the newest consecutive readings point away from the current stage, in the
     * same direction: 0 when the newest reading agrees with it.
     *
     * @param  list<int>  $history
     */
    private function pointing(int $stage, array $history): int
    {
        $count = 0;
        $direction = null;
        foreach (array_reverse($history) as $indicated) {
            $side = $indicated <=> $stage;
            if ($side === 0 || ($direction !== null && $side !== $direction)) {
                break;
            }
            $direction = $side;
            $count++;
        }

        return $count;
    }
}
