<?php

declare(strict_types=1);

namespace App\Domain\Secondary;

use Brick\Math\BigInteger;

/**
 * The unit identities one holder owns in one note, as fixed ordinal ranges. A sale moves the
 * lot's lowest ordinals to the buyer and the seller keeps the rest, so units keep their identity
 * from issue to maturity and every payout can be traced to the holder of each unit.
 */
final readonly class HoldingLot
{
    /** @param list<array{first: string, last: string}> $ranges ascending, disjoint */
    private function __construct(public array $ranges, public BigInteger $units) {}

    /** @param list<array{first: string, last: string}> $ranges */
    public static function of(array $ranges): self
    {
        $units = BigInteger::zero();
        $previous = null;
        foreach ($ranges as $range) {
            $first = Quantity::units($range['first']);
            $last = Quantity::units($range['last']);
            if ($first->isGreaterThan($last) || ($previous !== null && $first->isLessThanOrEqualTo($previous))) {
                throw new SecondaryViolation('INVALID_ORDINALS');
            }
            $units = $units->plus($last->minus($first)->plus(1));
            $previous = $last;
        }
        if ($units->isZero()) {
            throw new SecondaryViolation('INVALID_ORDINALS');
        }

        return new self($ranges, $units);
    }

    /**
     * The lowest `units` ordinals moved to the buyer, and what the seller keeps (null when the
     * whole lot moves).
     *
     * @return array{moved: self, kept: self|null}
     */
    public function split(string $units): array
    {
        $wanted = Quantity::units($units);
        if ($wanted->isGreaterThan($this->units)) {
            throw new SecondaryViolation('UNITS_NOT_HELD');
        }
        $moved = [];
        $kept = [];
        $left = $wanted;
        foreach ($this->ranges as $range) {
            $first = BigInteger::of($range['first']);
            $last = BigInteger::of($range['last']);
            $size = $last->minus($first)->plus(1);
            if ($left->isZero()) {
                $kept[] = $range;
            } elseif ($size->isLessThanOrEqualTo($left)) {
                $moved[] = $range;
                $left = $left->minus($size);
            } else {
                $boundary = $first->plus($left)->minus(1);
                $moved[] = ['first' => $range['first'], 'last' => (string) $boundary];
                $kept[] = ['first' => (string) $boundary->plus(1), 'last' => $range['last']];
                $left = BigInteger::zero();
            }
        }

        return ['moved' => self::of($moved), 'kept' => $kept === [] ? null : self::of($kept)];
    }
}
