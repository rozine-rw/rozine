<?php

declare(strict_types=1);

namespace App\Domain\Business;

use DateTimeImmutable;

/**
 * The two milestones toward the capital market (BRS v3.0 RDY-R08), each a list of its conditions
 * in BRS order. A condition is `met`, `not_met`, or `pending` while Rozine cannot measure it yet:
 *
 * - Bond track: stage 3 or 4, Ready for 6 consecutive months, at least 3 Rozine notes with every
 *   instalment collected on its due day, and audited annual statements (requirement F4 Met).
 * - RSE Investment Clinic referral: a limited company, at least 3 years trading, no major
 *   proceedings, stage 3 or 4 and Ready for 12 consecutive months.
 *
 * Ready is not tracked month by month yet, instalment collection belongs to servicing and no
 * record of proceedings exists, so those stay `pending` and no milestone is reached before they
 * can be read. Fewer than 3 funded notes is already `not_met`, whatever servicing will say.
 * A limited company is an organisation registered with an RDB company code. Trading is counted
 * from the last day of the year the business was established, so 3 years are never overstated.
 *
 * @phpstan-type Facts array{stage: int|null, company: bool, established_year: int|null, notes: int, audited: bool}
 * @phpstan-type Condition array{key: string, state: 'met'|'not_met'|'pending'}
 * @phpstan-type Track array{reached: bool, conditions: list<Condition>}
 */
final class ReadinessMilestones
{
    public const int BOND_READY_MONTHS = 6;

    public const int CLINIC_READY_MONTHS = 12;

    public const int BOND_NOTES = 3;

    public const int CLINIC_TRADING_YEARS = 3;

    /**
     * @param  Facts  $facts
     * @return array{bond: Track, clinic: Track}
     */
    public static function assess(array $facts, DateTimeImmutable $today): array
    {
        $stage = self::state($facts['stage'] !== null && $facts['stage'] >= 3);
        $year = $facts['established_year'];

        return [
            'bond' => self::track([
                ['key' => 'stage', 'state' => $stage],
                ['key' => 'ready_6_months', 'state' => 'pending'],
                ['key' => 'notes_on_time', 'state' => $facts['notes'] < self::BOND_NOTES ? 'not_met' : 'pending'],
                ['key' => 'audited_statements', 'state' => self::state($facts['audited'])],
            ]),
            'clinic' => self::track([
                ['key' => 'limited_company', 'state' => self::state($facts['company'])],
                ['key' => 'trading_3_years', 'state' => $year === null ? 'pending'
                    : self::state($today >= new DateTimeImmutable(($year + self::CLINIC_TRADING_YEARS).'-12-31'))],
                ['key' => 'no_proceedings', 'state' => 'pending'],
                ['key' => 'stage', 'state' => $stage],
                ['key' => 'ready_12_months', 'state' => 'pending'],
            ]),
        ];
    }

    /** @return 'met'|'not_met' */
    private static function state(bool $met): string
    {
        return $met ? 'met' : 'not_met';
    }

    /**
     * @param  list<Condition>  $conditions
     * @return Track
     */
    private static function track(array $conditions): array
    {
        return ['reached' => array_unique(array_column($conditions, 'state')) === ['met'], 'conditions' => $conditions];
    }
}
