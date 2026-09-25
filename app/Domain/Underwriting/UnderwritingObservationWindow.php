<?php

declare(strict_types=1);

namespace App\Domain\Underwriting;

use DateTimeImmutable;
use DateTimeZone;

/**
 * @phpstan-import-type MonthInput from CashFlowEvidence
 *
 * @phpstan-type Calendar array{last_complete_month: string, first_repayment_month: string}
 * @phpstan-type Selection array{months: list<MonthInput>, calendar: Calendar, valid_through: string|null, fresh: bool}
 */
final class UnderwritingObservationWindow
{
    public const string VERSION = 'kigali-observation-window-1';

    /**
     * Select the latest complete audited 36/12-month window. Older observations remain
     * in their immutable source; current and future partial months never enter the calculation.
     * Missing, duplicated or unverified selected months remain for the cash-flow gate to reject.
     *
     * @param  list<MonthInput>  $months
     * @return Selection
     */
    public function select(array $months, DateTimeImmutable $instant, bool $repeat): array
    {
        $local = $instant->setTimezone(new DateTimeZone('Africa/Kigali'));
        $currentMonth = $local->format('Y-m');
        $complete = [];
        foreach ($months as $month) {
            if (! preg_match('/^[1-9][0-9]{3}-(0[1-9]|1[0-2])$/D', $month['month'])) {
                throw new UnderwritingViolation('INVALID_OBSERVATION_MONTH');
            }
            if ($month['month'] < $currentMonth) {
                $complete[] = $month;
            }
        }
        usort($complete, fn (array $left, array $right): int => strcmp($left['month'], $right['month']));
        $lastLabel = $complete === [] ? $local->modify('first day of this month')->modify('-1 month')->format('Y-m') : $complete[count($complete) - 1]['month'];
        $last = new DateTimeImmutable($lastLabel.'-01', new DateTimeZone('Africa/Kigali'));
        $first = $last->modify($repeat ? '-11 months' : '-35 months')->format('Y-m');
        $selected = array_values(array_filter($complete, fn (array $month): bool => $month['month'] >= $first));
        $validThrough = $complete === [] ? null : $last->modify('last day of this month')->modify('+45 days')->format('Y-m-d');

        return ['months' => $selected,
            'calendar' => ['last_complete_month' => $lastLabel, 'first_repayment_month' => $currentMonth],
            'valid_through' => $validThrough, 'fresh' => $validThrough !== null && $local->format('Y-m-d') <= $validThrough];
    }
}
