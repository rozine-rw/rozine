<?php

declare(strict_types=1);

namespace App\Domain\Operations;

use Brick\Math\BigInteger;

/**
 * The policies the console may change, and how a change takes effect (Erastus, 26 Nov 2026). Each
 * setting has the range a value must fall in; a change is proposed by staff holding `policies.change`
 * and applies only once FOUNDER_APPROVALS different founders (`policies.approve`) have approved it.
 * One founder refusing it closes it. Nothing here reads or writes storage.
 *
 * - `investor_cap_percent`: the most of one raise an individual Investor may hold (replaces INV-R01's 50%).
 * - `institution_cap_percent`: the same for an institutional investor. Caps are per investor only, so
 *   any mix of investors may fill a raise.
 * - `institution_min_commitment`: the least an institution commits to distribute each month, in RWF.
 * - `institution_reminder_day`: the day of the month top-up reminders for next month start.
 */
final class PolicyChange
{
    /** Founders who must each approve a change before it takes effect. */
    public const int FOUNDER_APPROVALS = 2;

    /** @var array<string, array{unit: 'percent'|'rwf'|'day', min: int, max: int, default: int}> */
    public const array SETTINGS = [
        'investor_cap_percent' => ['unit' => 'percent', 'min' => 1, 'max' => 100, 'default' => 10],
        'institution_cap_percent' => ['unit' => 'percent', 'min' => 1, 'max' => 100, 'default' => 25],
        'institution_min_commitment' => ['unit' => 'rwf', 'min' => 1000000, 'max' => 5000000000, 'default' => 30000000],
        'institution_reminder_day' => ['unit' => 'day', 'min' => 1, 'max' => 28, 'default' => 25],
    ];

    /**
     * The field errors a proposed value has, none when it may be proposed.
     *
     * @return array<string, list<string>>
     */
    public static function problems(string $key, string $value, int $current): array
    {
        $setting = self::SETTINGS[$key] ?? null;
        if ($setting === null) {
            return ['key' => ['Choose a policy to change.']];
        }
        $value = (string) preg_replace('/[\s,]+/', '', $value);
        if (preg_match('/^[0-9]{1,13}$/D', $value) !== 1 || (int) $value < $setting['min'] || (int) $value > $setting['max']) {
            return ['value' => [sprintf('Enter a whole number from %s to %s.', number_format($setting['min']), number_format($setting['max']))]];
        }
        if ((int) $value === $current) {
            return ['value' => ['This is already the value in force.']];
        }

        return [];
    }

    /** The value a proposal carries, once `problems` found none. */
    public static function value(string $value): int
    {
        return (int) preg_replace('/[\s,]+/', '', $value);
    }

    /**
     * Where a change stands after a founder's decision: still `pending` until enough founders have
     * approved, `applied` once they have, `rejected` as soon as one refuses.
     *
     * @param  list<'approve'|'reject'>  $decisions  every founder decision, this one included
     */
    public static function outcome(array $decisions): string
    {
        return match (true) {
            in_array('reject', $decisions, true) => 'rejected',
            count($decisions) >= self::FOUNDER_APPROVALS => 'applied',
            default => 'pending',
        };
    }

    /**
     * The most units of a raise one investor may hold under a cap, rounded down.
     */
    public static function capUnits(string $raiseUnits, int $capPercent): string
    {
        return (string) BigInteger::of($raiseUnits)->multipliedBy($capPercent)->quotient(100);
    }
}
