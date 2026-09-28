<?php

declare(strict_types=1);

namespace App\Domain\Primary;

use Brick\Math\BigInteger;

/** Fixed, one-based unit identities; storage grows with ranges, not campaign size. */
final readonly class UnitOrdinals
{
    /** @param list<array{first: string, last: string}> $ranges */
    private function __construct(public BigInteger $totalUnits, public array $ranges, public BigInteger $count) {}

    public static function quantity(string $value): BigInteger
    {
        if (! preg_match('/^[1-9][0-9]{0,63}$/D', $value)) {
            throw new PrimaryViolation('INVALID_UNITS');
        }

        return BigInteger::of($value);
    }

    /** @param list<array{first: string, last: string}> $ranges */
    public static function fromRanges(string $totalUnits, array $ranges): self
    {
        $total = self::quantity($totalUnits);
        $parsed = [];
        foreach ($ranges as $range) {
            $first = self::quantity($range['first']);
            $last = self::quantity($range['last']);
            if ($first->isGreaterThan($last) || $last->isGreaterThan($total)) {
                throw new PrimaryViolation('INVALID_ORDINALS');
            }
            $parsed[] = ['first' => $first, 'last' => $last];
        }
        usort($parsed, fn (array $left, array $right): int => $left['first']->compareTo($right['first']));
        $normalized = [];
        $count = BigInteger::zero();
        foreach ($parsed as $range) {
            $previous = array_key_last($normalized);
            if ($previous !== null && $range['first']->isLessThanOrEqualTo($normalized[$previous]['last'])) {
                throw new PrimaryViolation('OVERLAPPING_ORDINALS');
            }
            $count = $count->plus($range['last']->minus($range['first'])->plus(1));
            if ($previous !== null && $range['first']->isEqualTo(BigInteger::of($normalized[$previous]['last'])->plus(1))) {
                $normalized[$previous]['last'] = (string) $range['last'];
            } else {
                $normalized[] = ['first' => (string) $range['first'], 'last' => (string) $range['last']];
            }
        }

        return new self($total, $normalized, $count);
    }

    /**
     * Select the lowest free identities under the caller's campaign lock.
     * Occupied includes both live reservations and unrefunded commitments.
     *
     * @param  list<array{first: string, last: string}>  $occupied
     */
    public static function reserve(string $totalUnits, array $occupied, string $quantity): self
    {
        $requested = self::quantity($quantity);
        $used = self::fromRanges($totalUnits, $occupied);
        if ($requested->isGreaterThan($used->totalUnits->minus($used->count))) {
            throw new PrimaryViolation('INSUFFICIENT_UNITS');
        }
        $remaining = $requested;
        $cursor = BigInteger::one();
        $selected = [];
        $boundaries = [...$used->ranges, ['first' => (string) $used->totalUnits->plus(1), 'last' => (string) $used->totalUnits]];
        foreach ($boundaries as $range) {
            $gap = BigInteger::of($range['first'])->minus($cursor);
            if ($gap->isPositive()) {
                $take = BigInteger::min($gap, $remaining);
                $selected[] = ['first' => (string) $cursor, 'last' => (string) $cursor->plus($take)->minus(1)];
                $remaining = $remaining->minus($take);
                if ($remaining->isZero()) {
                    break;
                }
            }
            $cursor = BigInteger::of($range['last'])->plus(1);
        }

        return new self($used->totalUnits, $selected, $requested);
    }

    public function countBetween(BigInteger $first, BigInteger $last): BigInteger
    {
        $count = BigInteger::zero();
        foreach ($this->ranges as $range) {
            $start = BigInteger::max($first, $range['first']);
            $end = BigInteger::min($last, $range['last']);
            $count = $count->plus(BigInteger::max(0, $end->minus($start)->plus(1)));
        }

        return $count;
    }
}
