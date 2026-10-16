<?php

declare(strict_types=1);

namespace App\Domain\Primary;

use Brick\Math\BigInteger;

/**
 * CFG-04 arithmetic for one receipt and one frozen contractual component. The caller must supply
 * complete authenticated unpaid, record-date unit entitlements; this value grants no payment
 * authority. Receipt identity, component priority, fees and persisted replay belong to servicing.
 * Ranges describe equal unpaid amounts PER UNIT, never one aggregate weight per Holding.
 */
final readonly class RepaymentComponentAllocation
{
    /** @param list<array{first: string, last: string, unpaid: string, paid: string, remaining: string}> $ranges */
    private function __construct(public string $received, public string $applied, public string $unapplied, public array $ranges) {}

    /**
     * Repeat independently against the remaining entitlements for each actual receipt. Do not
     * merge or split receipt identities to change rounding. Excess remains borrower unapplied cash.
     *
     * @param  array<array-key, array{first: string, last: string, unpaid: string}>  $entitlements
     */
    public static function allocate(string $receipt, array $entitlements): self
    {
        $received = self::amount($receipt);
        if (! array_is_list($entitlements)) {
            throw new PrimaryViolation('INVALID_REPAYMENT_ENTITLEMENTS');
        }
        $ranges = [];
        foreach ($entitlements as $entitlement) {
            $first = UnitOrdinals::quantity($entitlement['first']);
            $last = UnitOrdinals::quantity($entitlement['last']);
            if ($first->isGreaterThan($last)) {
                throw new PrimaryViolation('INVALID_REPAYMENT_ENTITLEMENTS');
            }
            $ranges[] = ['first' => $first, 'last' => $last, 'unpaid' => self::amount($entitlement['unpaid'])];
        }
        usort($ranges, fn (array $left, array $right): int => $left['first']->compareTo($right['first']));
        $total = BigInteger::zero();
        $previous = BigInteger::zero();
        foreach ($ranges as $range) {
            if ($range['first']->isLessThanOrEqualTo($previous)) {
                throw new PrimaryViolation('INVALID_REPAYMENT_ENTITLEMENTS');
            }
            $previous = $range['last'];
            $total = $total->plus($range['unpaid']->multipliedBy($range['last']->minus($range['first'])->plus(1)));
        }
        $applied = BigInteger::min($received, $total);
        $residual = $applied;
        $ranked = [];
        foreach ($ranges as $range) {
            [$base, $remainder] = $total->isZero() ? [BigInteger::zero(), BigInteger::zero()]
                : $range['unpaid']->multipliedBy($applied)->quotientAndRemainder($total);
            $ranked[] = [...$range, 'paid' => $base, 'remainder' => $remainder];
            $residual = $residual->minus($base->multipliedBy($range['last']->minus($range['first'])->plus(1)));
        }
        usort($ranked, fn (array $left, array $right): int => $right['remainder']->compareTo($left['remainder']) ?: $left['first']->compareTo($right['first']));
        $allocated = [];
        foreach ($ranked as $range) {
            $extra = BigInteger::min($residual, $range['last']->minus($range['first'])->plus(1));
            $boundary = $range['first']->plus($extra);
            if (! $extra->isZero()) {
                $allocated[] = self::result($range['first'], $boundary->minus(1), $range['unpaid'], $range['paid']->plus(1));
            }
            if ($boundary->isLessThanOrEqualTo($range['last'])) {
                $allocated[] = self::result($boundary, $range['last'], $range['unpaid'], $range['paid']);
            }
            $residual = $residual->minus($extra);
        }
        usort($allocated, fn (array $left, array $right): int => BigInteger::of($left['first'])->compareTo($right['first']));
        $normalized = [];
        foreach ($allocated as $range) {
            $last = array_key_last($normalized);
            if ($last !== null && BigInteger::of($normalized[$last]['last'])->plus(1)->isEqualTo($range['first'])
                && $normalized[$last]['unpaid'] === $range['unpaid'] && $normalized[$last]['paid'] === $range['paid']) {
                $normalized[$last]['last'] = $range['last'];
            } else {
                $normalized[] = $range;
            }
        }

        return new self((string) $received, (string) $applied, (string) $received->minus($applied), $normalized);
    }

    /** @return array{first: string, last: string, unpaid: string, paid: string, remaining: string} */
    private static function result(BigInteger $first, BigInteger $last, BigInteger $unpaid, BigInteger $paid): array
    {
        return ['first' => (string) $first, 'last' => (string) $last, 'unpaid' => (string) $unpaid,
            'paid' => (string) $paid, 'remaining' => (string) $unpaid->minus($paid)];
    }

    private static function amount(string $value): BigInteger
    {
        if (! preg_match('/^(0|[1-9][0-9]{0,63})$/D', $value)) {
            throw new PrimaryViolation('INVALID_MONEY');
        }

        return BigInteger::of($value);
    }
}
