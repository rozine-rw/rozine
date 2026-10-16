<?php

declare(strict_types=1);

use App\Domain\Primary\PrimaryViolation;
use App\Domain\Primary\RepaymentComponentAllocation;

/**
 * @param  list<int>  $amounts
 * @return list<array{first: string, last: string, unpaid: string}>
 */
function repaymentUnpaidUnits(array $amounts): array
{
    return array_map(fn (int $index, int $amount): array => ['first' => (string) ($index + 1), 'last' => (string) ($index + 1), 'unpaid' => (string) $amount], array_keys($amounts), $amounts);
}

/**
 * @param  list<int>  $unpaid
 * @return list<int>
 */
function repaymentExplicitReference(int $receipt, array $unpaid): array
{
    $total = array_sum($unpaid);
    $applied = min($receipt, $total);
    $paid = array_map(fn (int $amount): int => $total === 0 ? 0 : intdiv($applied * $amount, $total), $unpaid);
    $ordinals = array_keys($unpaid);
    usort($ordinals, fn (int $left, int $right): int => (($applied * $unpaid[$right]) % max(1, $total)) <=> (($applied * $unpaid[$left]) % max(1, $total)) ?: $left <=> $right);
    foreach (array_slice($ordinals, 0, $applied - array_sum($paid)) as $ordinal) {
        $paid[$ordinal]++;
    }

    return array_values($paid);
}

/** @return list<int> */
function repaymentExpandedPaid(RepaymentComponentAllocation $allocation): array
{
    $paid = [];
    foreach ($allocation->ranges as $range) {
        foreach (range((int) $range['first'], (int) $range['last']) as $ordinal) {
            $paid[$ordinal - 1] = (int) $range['paid'];
        }
    }

    return array_values($paid);
}

it('uses unpaid weights and awards largest remainders before lower ordinal ties', function (): void {
    $plan = RepaymentComponentAllocation::allocate('4', repaymentUnpaidUnits([1, 2, 3]));
    expect(repaymentExpandedPaid($plan))->toBe([1, 1, 2])
        ->and([$plan->received, $plan->applied, $plan->unapplied])->toBe(['4', '4', '0']);
    $tie = RepaymentComponentAllocation::allocate('2', repaymentUnpaidUnits([3, 3, 3]));
    expect(repaymentExpandedPaid($tie))->toBe([1, 1, 0]);
    $weighted = RepaymentComponentAllocation::allocate('1', repaymentUnpaidUnits([1, 2]));
    expect(repaymentExpandedPaid($weighted))->toBe([0, 1]);
});

it('is independent of input ordering and adjacent range segmentation', function (): void {
    $whole = [['first' => '1', 'last' => '9', 'unpaid' => '7'], ['first' => '12', 'last' => '15', 'unpaid' => '3']];
    $fragments = [['first' => '14', 'last' => '15', 'unpaid' => '3'], ['first' => '5', 'last' => '9', 'unpaid' => '7'],
        ['first' => '12', 'last' => '13', 'unpaid' => '3'], ['first' => '1', 'last' => '4', 'unpaid' => '7']];
    foreach (range(0, 80) as $receipt) {
        expect(RepaymentComponentAllocation::allocate((string) $receipt, $fragments))
            ->toEqual(RepaymentComponentAllocation::allocate((string) $receipt, $whole));
    }
});

it('agrees with an independent explicit-unit reference and conserves every franc', function (): void {
    foreach (range(0, 5) as $first) {
        foreach (range(0, 5) as $second) {
            foreach (range(0, 5) as $third) {
                $unpaid = [$first, $second, $third];
                foreach (range(0, array_sum($unpaid) + 2) as $receipt) {
                    $plan = RepaymentComponentAllocation::allocate((string) $receipt, repaymentUnpaidUnits($unpaid));
                    $paid = repaymentExpandedPaid($plan);
                    expect($paid)->toBe(repaymentExplicitReference($receipt, $unpaid))
                        ->and(array_sum($paid) + (int) $plan->unapplied)->toBe($receipt);
                    foreach ($plan->ranges as $range) {
                        expect((int) $range['paid'] + (int) $range['remaining'])->toBe((int) $range['unpaid'])
                            ->and((int) $range['remaining'])->toBeGreaterThanOrEqual(0);
                    }
                }
            }
        }
    }
});

it('recalculates each receipt against remaining entitlements and clears final balances', function (): void {
    $unpaid = [1, 1, 1];
    $paid = [0, 0, 0];
    foreach ([1, 1, 3] as $receipt) {
        $plan = RepaymentComponentAllocation::allocate((string) $receipt, repaymentUnpaidUnits($unpaid));
        $next = repaymentExpandedPaid($plan);
        $paid = array_map(fn (int $prior, int $amount): int => $prior + $amount, $paid, $next);
        $unpaid = array_map(fn (int $prior, int $amount): int => $prior - $amount, $unpaid, $next);
    }
    expect($paid)->toBe([1, 1, 1])->and($unpaid)->toBe([0, 0, 0])->and($plan->unapplied)->toBe('2');
    expect(RepaymentComponentAllocation::allocate('5', []))->toMatchObject(['received' => '5', 'applied' => '0', 'unapplied' => '5', 'ranges' => []]);
});

it('uses arbitrary precision and compressed ranges beyond native integer unit counts', function (): void {
    $last = '1000000000000000000000000000000';
    $plan = RepaymentComponentAllocation::allocate('1000000000000000000000000000001', [['first' => '1', 'last' => $last, 'unpaid' => '3']]);
    expect($plan->ranges)->toBe([
        ['first' => '1', 'last' => '1', 'unpaid' => '3', 'paid' => '2', 'remaining' => '1'],
        ['first' => '2', 'last' => $last, 'unpaid' => '3', 'paid' => '1', 'remaining' => '2'],
    ])->and($plan->unapplied)->toBe('0');
    $huge = str_repeat('9', 64);
    $full = RepaymentComponentAllocation::allocate($huge, [['first' => '1', 'last' => '1', 'unpaid' => $huge]]);
    expect($full->applied)->toBe($huge)->and($full->ranges[0]['remaining'])->toBe('0');
});

it('does not carry a previous receipt rounding cursor or replace real receipts with an aggregate', function (): void {
    $first = RepaymentComponentAllocation::allocate('1', repaymentUnpaidUnits([1, 1, 3]));
    $second = RepaymentComponentAllocation::allocate('1', repaymentUnpaidUnits([1, 1, 2]));
    $combined = RepaymentComponentAllocation::allocate('2', repaymentUnpaidUnits([1, 1, 3]));
    expect(repaymentExpandedPaid($first))->toBe([0, 0, 1])
        ->and(repaymentExpandedPaid($second))->toBe([0, 0, 1])
        ->and(repaymentExpandedPaid($combined))->toBe([1, 0, 1]);
    $replay = RepaymentComponentAllocation::allocate('1', repaymentUnpaidUnits([1, 1, 3]));
    expect($replay)->toEqual($first);
    foreach (['received', 'applied', 'unapplied', 'ranges'] as $property) {
        $reflection = new ReflectionProperty($first, $property);
        expect(fn () => $reflection->setValue($first, null))->toThrow(Error::class);
    }
});

it('retains gaps and zero entitlements without giving them a residual', function (): void {
    $plan = RepaymentComponentAllocation::allocate('1', [
        ['first' => '10', 'last' => '11', 'unpaid' => '2'],
        ['first' => '3', 'last' => '3', 'unpaid' => '0'],
    ]);
    expect($plan->ranges)->toBe([
        ['first' => '3', 'last' => '3', 'unpaid' => '0', 'paid' => '0', 'remaining' => '0'],
        ['first' => '10', 'last' => '10', 'unpaid' => '2', 'paid' => '1', 'remaining' => '1'],
        ['first' => '11', 'last' => '11', 'unpaid' => '2', 'paid' => '0', 'remaining' => '2'],
    ]);
});

it('refuses noncanonical receipt or unpaid amounts', function (string $amount): void {
    expect(fn () => RepaymentComponentAllocation::allocate($amount, []))->toThrow(PrimaryViolation::class, 'INVALID_MONEY')
        ->and(fn () => RepaymentComponentAllocation::allocate('1', [['first' => '1', 'last' => '2', 'unpaid' => $amount]]))->toThrow(PrimaryViolation::class, 'INVALID_MONEY');
})->with(['-1', '01', '1.5', '1e3', '', ' 1', str_repeat('9', 65)]);

it('refuses malformed ordinals', function (string $ordinal): void {
    expect(fn () => RepaymentComponentAllocation::allocate('1', [['first' => $ordinal, 'last' => '2', 'unpaid' => '1']]))->toThrow(PrimaryViolation::class, 'INVALID_UNITS');
})->with(['0', '-1', '01', '1.5', '', str_repeat('9', 65)]);

it('refuses reversed overlapping duplicate and non-list entitlement ranges', function (array $ranges): void {
    expect(fn () => RepaymentComponentAllocation::allocate('1', $ranges))->toThrow(PrimaryViolation::class, 'INVALID_REPAYMENT_ENTITLEMENTS');
})->with([
    [[['first' => '2', 'last' => '1', 'unpaid' => '1']]],
    [[['first' => '1', 'last' => '3', 'unpaid' => '2'], ['first' => '3', 'last' => '5', 'unpaid' => '1']]],
    [[['first' => '1', 'last' => '1', 'unpaid' => '2'], ['first' => '1', 'last' => '1', 'unpaid' => '2']]],
    [[1 => ['first' => '1', 'last' => '1', 'unpaid' => '2']]],
]);
