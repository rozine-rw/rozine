<?php

declare(strict_types=1);

namespace App\Domain\Business;

use Carbon\CarbonImmutable;

/**
 * The nine structural requirements size triggers (BRS v3.0 RDY-R03, built to the proposed OD-21
 * triggers and grace periods). C1 and F1 apply to every business from entry, F1 being checked by
 * the entry statement audit itself. The size triggers read the settled readiness stage rather than
 * one VMR reading, so a requirement is never triggered by a reading the stage would not follow;
 * once triggered it stays in force when the stage falls. P1 and P2 follow headcount, and G1's
 * second trigger follows Rozine principal; neither number has a verified source yet, so those
 * triggers wait for it. Status runs Due → Overdue on the exact Kigali day the grace ends, Due or
 * Overdue → Met when the CPA confirms it, and Met → Lapsed → Overdue 60 days after it lapses.
 *
 * @phpstan-type Code 'C1'|'F1'|'F2'|'F3'|'F4'|'P1'|'P2'|'G1'|'G2'
 * @phpstan-type Status 'due'|'overdue'|'met'|'lapsed'
 * @phpstan-type Rule array{trigger: 'always'|'stage'|'headcount', from: int, grace: int|'year_end'|'entry'|'expiry'}
 * @phpstan-type Stored array{code: Code, status: Status, due_on: string|null, met_on: string|null}
 * @phpstan-type Listed array{code: Code, status: Status|'not_triggered', due_on: string|null, days_left: int|null, met_on: string|null,
 *     applies_from: array{kind: 'stage'|'headcount'|'entry', value: int}|null}
 */
final class ReadinessRequirements
{
    public const string POLICY_VERSION = 'brs-3.0-rdy-r03-od-21-proposed';

    /** Days a lapsed requirement has to be met again before it is Overdue. */
    public const int LAPSE_DAYS = 60;

    /** Days before the grace ends that the business is reminded. */
    public const int REMINDER_DAYS = 14;

    /**
     * Every requirement in the order the BRS lists it. `from` is the stage (or headcount) that
     * triggers it; C1's grace counts from the expiry of the evidence the CPA records.
     *
     * @var array<Code, Rule>
     */
    public const array RULES = [
        'C1' => ['trigger' => 'always', 'from' => 0, 'grace' => 'expiry'],
        'F1' => ['trigger' => 'always', 'from' => 0, 'grace' => 'entry'],
        'F2' => ['trigger' => 'stage', 'from' => 2, 'grace' => 90],
        'F3' => ['trigger' => 'stage', 'from' => 3, 'grace' => 120],
        'F4' => ['trigger' => 'stage', 'from' => 4, 'grace' => 'year_end'],
        'P1' => ['trigger' => 'headcount', 'from' => 10, 'grace' => 60],
        'P2' => ['trigger' => 'headcount', 'from' => 30, 'grace' => 90],
        'G1' => ['trigger' => 'stage', 'from' => 3, 'grace' => 120],
        'G2' => ['trigger' => 'stage', 'from' => 4, 'grace' => 180],
    ];

    /**
     * The requirements in force at this settled stage, in BRS order. C1 and F1 apply from entry.
     *
     * @return list<Code>
     */
    public function triggered(int $stage): array
    {
        return array_keys(array_filter(self::RULES, fn (array $rule): bool => $rule['trigger'] === 'always'
            || ($rule['trigger'] === 'stage' && $stage >= $rule['from'])));
    }

    /**
     * The status and grace end a requirement starts with on the Kigali day it is triggered. C1 and
     * F1 are met at entry: the business passed KYB and its entry statement audit to be listed.
     *
     * @param  Code  $code
     * @return array{status: Status, due_on: string|null}
     */
    public function start(string $code, string $today): array
    {
        $grace = self::RULES[$code]['grace'];
        if (! is_int($grace) && $grace !== 'year_end') {
            return ['status' => 'met', 'due_on' => null];
        }
        $day = CarbonImmutable::parse($today, 'Africa/Kigali');

        return ['status' => 'due', 'due_on' => $grace === 'year_end' ? $day->endOfYear()->toDateString() : $day->addDays($grace)->toDateString()];
    }

    /**
     * The status a requirement holds on the Kigali day `$today`: Due and Lapsed turn Overdue on
     * the exact day their grace ends.
     *
     * @param  Status  $status
     * @return Status
     */
    public function on(string $status, ?string $dueOn, string $today): string
    {
        return in_array($status, ['due', 'lapsed'], true) && $dueOn !== null && $today >= $dueOn ? 'overdue' : $status;
    }

    /** Whole Kigali days from `$today` to the end of the grace; negative once it has passed. */
    public function daysLeft(string $dueOn, string $today): int
    {
        return (int) CarbonImmutable::parse($today, 'Africa/Kigali')->diffInDays(CarbonImmutable::parse($dueOn, 'Africa/Kigali'), false);
    }

    /** The grace end a requirement gets when it lapses on `$today`. */
    public function lapsedDue(string $today): string
    {
        return CarbonImmutable::parse($today, 'Africa/Kigali')->addDays(self::LAPSE_DAYS)->toDateString();
    }

    /**
     * Every requirement in BRS order as the Business sees it on `$today`: a stored one with the
     * status it holds that day, C1 and F1 Met from entry before the first sweep records them (only
     * once `$entered`: a published audit report is the entry evidence, so before it they apply from
     * entry and nothing is shown as completed), and the rest not triggered with the stage or
     * headcount they apply from.
     *
     * @param  list<Stored>  $stored
     * @return list<Listed>
     */
    public function listed(array $stored, string $today, bool $entered): array
    {
        $rows = array_column($stored, null, 'code');
        $list = [];
        foreach (self::RULES as $code => $rule) {
            $row = $rows[$code] ?? null;
            $status = match (true) {
                $row !== null => $this->on($row['status'], $row['due_on'], $today),
                $rule['trigger'] === 'always' && $entered => 'met',
                default => 'not_triggered',
            };
            $dueOn = $row === null || $status === 'met' ? null : $row['due_on'];
            $list[] = ['code' => $code, 'status' => $status, 'due_on' => $dueOn,
                'days_left' => $dueOn === null ? null : max(0, $this->daysLeft($dueOn, $today)), 'met_on' => $row['met_on'] ?? null,
                'applies_from' => match (true) {
                    $rule['trigger'] !== 'always' => ['kind' => $rule['trigger'], 'value' => $rule['from']],
                    $status === 'not_triggered' => ['kind' => 'entry', 'value' => 0],
                    default => null,
                }];
        }

        return $list;
    }
}
