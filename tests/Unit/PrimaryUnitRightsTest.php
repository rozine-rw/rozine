<?php

declare(strict_types=1);

use App\Domain\Primary\PrimaryViolation;
use App\Domain\Primary\UnitOrdinals;
use App\Domain\Primary\UnitRights;
use App\Domain\Underwriting\LoanSchedule;
use Brick\Math\BigInteger;
use Brick\Math\BigRational;

/** @return array{shares: list<string>, cursor: int} */
function primaryReferenceComponent(int $amount, int $units, int $cursor): array
{
    $allocation = array_fill(0, $units, intdiv($amount, $units));
    for ($offset = 0; $offset < $amount % $units; $offset++) {
        $allocation[($cursor + $offset) % $units]++;
    }

    return ['shares' => array_map(strval(...), array_values($allocation)), 'cursor' => ($cursor + $amount % $units) % $units];
}

it('carries independent principal and return cursors instead of resetting each instalment', function (): void {
    $rights = UnitRights::allocate('10000', ['3667', '3667', '3666'], UnitOrdinals::fromRanges('2', [['first' => '1', 'last' => '1']]));
    expect((string) $rights->principal)->toBe('5000')->and($rights->toArray())->toBe([
        'total_return' => ['currency' => 'RWF', 'amount' => '500'],
        'instalments' => [
            ['index' => 1, 'principal' => ['currency' => 'RWF', 'amount' => '1667'], 'return' => ['currency' => 'RWF', 'amount' => '167']],
            ['index' => 2, 'principal' => ['currency' => 'RWF', 'amount' => '1666'], 'return' => ['currency' => 'RWF', 'amount' => '167']],
            ['index' => 3, 'principal' => ['currency' => 'RWF', 'amount' => '1667'], 'return' => ['currency' => 'RWF', 'amount' => '166']],
        ],
    ]);
});

it('conserves every published instalment and gives each fixed ordinal exactly 5000 principal', function (int $tenor): void {
    foreach (range(1, 20) as $units) {
        $principal = $units * 5000;
        $regular = (int) round($principal / $tenor, 0, PHP_ROUND_HALF_UP);
        $principalParts = array_fill(0, $tenor - 1, $regular);
        $principalParts[] = $principal - $regular * ($tenor - 1);
        $payments = array_map(fn (int $part, int $index): string => (string) ($part + 13 + $index * 19), $principalParts, range(0, $tenor - 1));
        $expected = [];
        $principalCursor = 0;
        $returnCursor = 0;
        foreach ($payments as $index => $payment) {
            $principalAllocation = primaryReferenceComponent($principalParts[$index], $units, $principalCursor);
            $returnAllocation = primaryReferenceComponent((int) $payment - $principalParts[$index], $units, $returnCursor);
            $principalCursor = $principalAllocation['cursor'];
            $returnCursor = $returnAllocation['cursor'];
            for ($ordinal = 1; $ordinal <= $units; $ordinal++) {
                $expected[$ordinal][$index] = ['principal' => $principalAllocation['shares'][$ordinal - 1], 'return' => $returnAllocation['shares'][$ordinal - 1]];
            }
        }
        $collected = array_fill(0, $tenor, 0);
        foreach ($expected as $ordinal => $instalments) {
            $rights = UnitRights::allocate((string) $principal, $payments, UnitOrdinals::fromRanges((string) $units, [['first' => (string) $ordinal, 'last' => (string) $ordinal]]));
            expect(array_sum(array_column($rights->instalments, 'principal')))->toBe(5000);
            foreach ($rights->instalments as $index => $instalment) {
                expect($instalment['principal'])->toBe($instalments[$index]['principal'])
                    ->and($instalment['return'])->toBe($instalments[$index]['return']);
                $collected[$index] += (int) $instalment['principal'] + (int) $instalment['return'];
            }
        }
        expect($collected)->toBe(array_map(intval(...), $payments));
    }
})->with([3, 4, 5, 6]);

it('preserves the approved 10700000 campaign schedule for whole and fragmented holdings', function (): void {
    $schedule = LoanSchedule::build('10700000', 6, BigRational::of('121/1000'));
    $payments = array_map(strval(...), $schedule->instalments);
    $all = UnitRights::allocate('10700000', $payments, UnitOrdinals::reserve('2140', [], '2140'));
    expect((string) $all->contractualReturn)->toBe('1294700')
        ->and(array_sum(array_column($all->instalments, 'principal')))->toBe(10700000);
    foreach ($all->instalments as $index => $instalment) {
        expect((string) BigInteger::of($instalment['principal'])->plus($instalment['return']))->toBe($payments[$index]);
    }
    $fragment = UnitOrdinals::fromRanges('2140', [['first' => '1', 'last' => '3'], ['first' => '2140', 'last' => '2140']]);
    $rights = UnitRights::allocate('10700000', $payments, $fragment);
    $separate = array_map(fn (int $ordinal): UnitRights => UnitRights::allocate('10700000', $payments,
        UnitOrdinals::fromRanges('2140', [['first' => (string) $ordinal, 'last' => (string) $ordinal]])), [1, 2, 3, 2140]);
    foreach ($rights->instalments as $index => $instalment) {
        foreach (['principal', 'return'] as $component) {
            expect($instalment[$component])->toBe((string) array_sum(array_map(fn (UnitRights $unit): int => (int) $unit->instalments[$index][$component], $separate)));
        }
    }
    expect($rights->toArray())->not->toHaveKey('maturity_date')->and((string) $rights->principal)->toBe('20000');
});

it('allocates huge schedules by ranges without native integer conversion or per-unit enumeration', function (): void {
    $principal = '500000000000000000000000';
    $payment = '175000000000000000000000';
    $rights = UnitRights::allocate($principal, [$payment, $payment, $payment], UnitOrdinals::reserve('100000000000000000000', [], '100000000000000000000'));
    expect((string) $rights->principal)->toBe($principal)->and((string) $rights->contractualReturn)->toBe('25000000000000000000000');
});

it('keeps zero-return component rights without inventing a fee or return', function (): void {
    $rights = UnitRights::allocate('15000', ['5000', '5000', '5000'], UnitOrdinals::reserve('3', [], '3'));
    expect((string) $rights->contractualReturn)->toBe('0')->and(array_column($rights->instalments, 'return'))->toBe(['0', '0', '0']);
});

it('rejects inconsistent campaign schedules and empty purchases', function (string $principal, array $payments, bool $empty): void {
    $ranges = $empty ? [] : [['first' => '1', 'last' => '1']];
    expect(fn () => UnitRights::allocate($principal, $payments, UnitOrdinals::fromRanges('2', $ranges)))
        ->toThrow(PrimaryViolation::class, 'INVALID_UNIT_SCHEDULE');
})->with([
    ['10000', ['5000', '5000'], false],
    ['10001', ['3667', '3667', '3667'], false],
    ['10000', ['1', '5000', '5000'], false],
    ['10000', ['5000', '5000', '5000'], true],
    ['10000', [1 => '5000', 2 => '5000', 3 => '5000'], false],
]);

it('refuses malformed monetary amounts', function (string $amount): void {
    expect(fn () => UnitRights::allocate('10000', [$amount, '5000', '5000'], UnitOrdinals::reserve('2', [], '1')))
        ->toThrow(PrimaryViolation::class, 'INVALID_MONEY');
})->with(['-1', '1.5', '01', '1e3', '', str_repeat('9', 65)]);
