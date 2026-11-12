<?php

declare(strict_types=1);

namespace App\Domain\Business;

use Brick\Math\BigDecimal;
use Brick\Math\BigInteger;
use Brick\Math\RoundingMode;

/**
 * Two of the three readiness signals (BRS v3.0 RDY-R04) and the monthly guidance line for each
 * (RDY-R06), built to the proposed OD-21 targets.
 *
 * Record Gap compares what the Business declared for a month (VIS-R04) with what its published
 * report verified: (|declared − verified money in| + |declared − verified money out|) ÷ (verified
 * money in + verified money out). Declared money out is the six cost categories plus what was paid
 * to owners, since a report verifies every outflow.
 *
 * Forecast Error compares each forecast with the verified money in of the month it forecast:
 * |forecast − verified| ÷ verified. Its bias says whether the forecasts ran above or below.
 *
 * Each reading averages the newest three months that have one. Cash Lead reads wallet instalment
 * timestamps from servicing, which no note has yet, so it is not measured here.
 *
 * Each guidance line states the value, the stage's target, the verdict and one tool-neutral
 * action; it never asks a question. No CPA cause code exists yet, so no line names a cause.
 *
 * @phpstan-type Verified array{period: string, revenue: string, costs: string}
 * @phpstan-type Declared array{period: string, money_in: string, money_out: string, forecast_period: string, forecast: string}
 * @phpstan-type Line array{signal: 'record_gap'|'forecast_error', value: string, target: string, on_target: bool, months: int,
 *     bias: 'over'|'under'|null, action: string}
 */
final class ReadinessSignals
{
    /** Readings averaged into one signal. */
    public const int WINDOW = 3;

    /** The highest on-target value of each signal, in percent, by stage 1 to 4 (Section 3). */
    public const array TARGETS = [
        'record_gap' => [1 => '10', 2 => '5', 3 => '3', 4 => '2'],
        'forecast_error' => [1 => '25', 2 => '15', 3 => '10', 4 => '7'],
    ];

    private const int SCALE = 8;

    /**
     * The verified months of published reports, newest report first as the port lists them: each
     * period once, at its newest report, with its inflow as money in and its outflow as money out.
     *
     * @param  list<array{period: string, inflow: string, outflow: string}>  $reports
     * @return list<Verified>
     */
    public static function verified(array $reports): array
    {
        $months = array_map(fn (array $report): array => ['period' => $report['period'], 'revenue' => $report['inflow'], 'costs' => $report['outflow']], $reports);

        return array_values(array_column(array_reverse($months), null, 'period'));
    }

    /**
     * The guidance lines for a Business at `$stage` (null or 0 is held to the Foundation targets),
     * one per signal that has a reading, Record Gap first.
     *
     * @param  list<Verified>  $verified
     * @param  list<Declared>  $declared
     * @return list<Line>
     */
    public function guidance(array $verified, array $declared, ?int $stage): array
    {
        $level = $stage === null || $stage < 1 ? 1 : $stage;
        $byPeriod = array_column($verified, null, 'period');
        $gaps = [];
        $errors = [];
        foreach ($declared as $month) {
            $report = $byPeriod[$month['period']] ?? null;
            if ($report !== null) {
                $base = BigInteger::of($report['revenue'])->plus(BigInteger::of($report['costs']));
                if (! $base->isZero()) {
                    $gaps[$month['period']] = BigInteger::of($month['money_in'])->minus(BigInteger::of($report['revenue']))->abs()
                        ->plus(BigInteger::of($month['money_out'])->minus(BigInteger::of($report['costs']))->abs())
                        ->toBigDecimal()->dividedBy($base->toBigDecimal(), self::SCALE, RoundingMode::HalfUp);
                }
            }
            $actual = $byPeriod[$month['forecast_period']] ?? null;
            if ($actual !== null && ! BigInteger::of($actual['revenue'])->isZero()) {
                $errors[$month['forecast_period']] = BigInteger::of($month['forecast'])->minus(BigInteger::of($actual['revenue']))
                    ->toBigDecimal()->dividedBy(BigInteger::of($actual['revenue'])->toBigDecimal(), self::SCALE, RoundingMode::HalfUp);
            }
        }
        $lines = [];
        if ($gaps !== []) {
            $lines[] = $this->line('record_gap', $this->newest($gaps), $level, null);
        }
        if ($errors !== []) {
            $recent = $this->newest($errors);
            $bias = array_reduce($recent, fn (BigDecimal $sum, BigDecimal $error): BigDecimal => $sum->plus($error), BigDecimal::zero());
            $lines[] = $this->line('forecast_error', array_map(fn (BigDecimal $error): BigDecimal => $error->abs(), $recent), $level,
                $bias->isZero() ? null : ($bias->isPositive() ? 'over' : 'under'));
        }

        return $lines;
    }

    /**
     * The newest `WINDOW` readings, by period.
     *
     * @param  array<string, BigDecimal>  $readings
     * @return list<BigDecimal>
     */
    private function newest(array $readings): array
    {
        krsort($readings);

        return array_values(array_slice($readings, 0, self::WINDOW));
    }

    /**
     * @param  'record_gap'|'forecast_error'  $signal
     * @param  list<BigDecimal>  $readings  at least one, each a share, not a percent
     * @param  'over'|'under'|null  $bias
     * @return Line
     */
    private function line(string $signal, array $readings, int $stage, ?string $bias): array
    {
        $average = array_reduce($readings, fn (BigDecimal $sum, BigDecimal $reading): BigDecimal => $sum->plus($reading), BigDecimal::zero())
            ->dividedBy(count($readings), self::SCALE, RoundingMode::HalfUp)->multipliedBy(100);
        $target = self::TARGETS[$signal][$stage];
        $onTarget = $average->isLessThanOrEqualTo($target);
        $action = $signal.($onTarget ? '.keep' : '.fix');

        return ['signal' => $signal, 'value' => (string) $average->toScale(1, RoundingMode::HalfUp), 'target' => $target, 'on_target' => $onTarget,
            'months' => count($readings), 'bias' => $bias, 'action' => $action];
    }
}
