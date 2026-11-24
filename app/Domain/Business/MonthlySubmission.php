<?php

declare(strict_types=1);

namespace App\Domain\Business;

use App\Domain\Operations\CommandRejection;
use Brick\Math\BigInteger;
use Brick\Math\RoundingMode;

/**
 * A Business's monthly submission (BRS v3.0 VIS-R04): what it declares for the month its monthly
 * audit covers, before the Audit Partner verifies it. Six screens: money in, costs in six
 * categories, paid to owners, next month's forecast, four yes/no changes, then a one-line summary.
 *
 * The forecast is pre-filled with the verified revenue of the Business's last three published
 * reports, averaged. A forecast more than 25% away from that pre-fill needs a signed order: the
 * Business names it here and the Audit Partner sees it at the visit. With no published report there
 * is nothing to pre-fill, and no change can be measured.
 *
 * Every amount is whole Rwandan francs. A submission is a declaration: once sent it is final.
 *
 * @phpstan-type Costs array{stock: string, staff: string, premises: string, transport: string, taxes: string, other: string}
 * @phpstan-type Changes array{owners: bool, premises: bool, borrowing: bool, customers: bool}
 * @phpstan-type Figures array{money_in: string, costs: Costs, costs_total: string, paid_to_owners: string, forecast: string, forecast_basis: string|null, order: string|null, changes: Changes}
 */
final class MonthlySubmission
{
    public const string POLICY_VERSION = 'brs-3.0-vis-r04';

    /** The CPA's six cost categories, in screen order. */
    public const array COST_CATEGORIES = ['stock', 'staff', 'premises', 'transport', 'taxes', 'other'];

    /** The four yes/no changes, in screen order. */
    public const array CHANGES = ['owners', 'premises', 'borrowing', 'customers'];

    /** A forecast this far from the pre-fill (as a share of it) needs a signed order. */
    public const string ORDER_THRESHOLD = '0.25';

    private const int MAX_DIGITS = 15;

    private const int MAX_ORDER_LENGTH = 200;

    /**
     * The verified 3-month average the forecast starts from: the mean verified revenue of the newest
     * three published periods, in whole francs rounded down, or null with none published.
     *
     * @param  list<array{period: string, revenue: string}>  $months  one per period, any order
     */
    public static function forecastBasis(array $months): ?string
    {
        usort($months, fn (array $left, array $right): int => strcmp($right['period'], $left['period']));
        $recent = array_slice($months, 0, 3);
        if ($recent === []) {
            return null;
        }
        $total = array_reduce($recent, fn (BigInteger $sum, array $month): BigInteger => $sum->plus(BigInteger::of($month['revenue'])), BigInteger::zero());

        return (string) $total->dividedBy(count($recent), RoundingMode::Down);
    }

    /** Whether `$forecast` is more than 25% away from `$basis`; never with no basis or a zero basis. */
    public static function needsOrder(string $forecast, ?string $basis): bool
    {
        if ($basis === null || BigInteger::of($basis)->isZero()) {
            return false;
        }
        $change = BigInteger::of($forecast)->minus(BigInteger::of($basis))->abs();

        return $change->toBigDecimal()->isGreaterThan(BigInteger::of($basis)->toBigDecimal()->multipliedBy(self::ORDER_THRESHOLD));
    }

    /**
     * The figures the form describes against the forecast basis. Refused with the reason for each
     * field that cannot describe one.
     *
     * @param  array<string, mixed>  $input
     * @return Figures
     */
    public static function figures(array $input, ?string $basis): array
    {
        $errors = [];
        $amount = function (mixed $value, string $field) use (&$errors): string {
            $text = is_string($value) || is_int($value) ? trim((string) $value) : '';
            if (! preg_match('/^(0|[1-9][0-9]{0,'.(self::MAX_DIGITS - 1).'})$/D', $text)) {
                $errors[$field] = ['Enter a whole amount in Rwandan francs, 0 or more.'];

                return '0';
            }

            return $text;
        };
        $moneyIn = $amount($input['money_in'] ?? null, 'money_in');
        $costsInput = is_array($input['costs'] ?? null) ? $input['costs'] : [];
        $costs = [];
        $costsTotal = BigInteger::zero();
        foreach (self::COST_CATEGORIES as $category) {
            $costs[$category] = $amount($costsInput[$category] ?? null, 'costs.'.$category);
            $costsTotal = $costsTotal->plus(BigInteger::of($costs[$category]));
        }
        $paid = $amount($input['paid_to_owners'] ?? null, 'paid_to_owners');
        $forecast = $amount($input['forecast'] ?? null, 'forecast');
        $orderText = is_string($input['order'] ?? null) ? trim($input['order']) : '';
        $order = null;
        if (! isset($errors['forecast']) && self::needsOrder($forecast, $basis)) {
            if ($orderText === '') {
                $errors['order'] = ['This forecast is more than 25% away from your verified average. Name the signed order behind it.'];
            } elseif (mb_strlen($orderText) > self::MAX_ORDER_LENGTH) {
                $errors['order'] = ['Keep the order to 200 characters.'];
            } else {
                $order = $orderText;
            }
        }
        $changesInput = is_array($input['changes'] ?? null) ? $input['changes'] : [];
        $changes = [];
        foreach (self::CHANGES as $change) {
            $value = $changesInput[$change] ?? null;
            if (! is_bool($value)) {
                $errors['changes.'.$change] = ['Answer yes or no.'];
            }
            $changes[$change] = $value === true;
        }
        if ($errors !== []) {
            throw new CommandRejection('VALIDATION_FAILED', 422, fieldErrors: $errors);
        }

        /** @var Changes $changes */
        return ['money_in' => $moneyIn, 'costs' => $costs, 'costs_total' => (string) $costsTotal, 'paid_to_owners' => $paid, 'forecast' => $forecast,
            'forecast_basis' => $basis, 'order' => $order, 'changes' => $changes];
    }
}
