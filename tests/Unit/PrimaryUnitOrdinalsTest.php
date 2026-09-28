<?php

declare(strict_types=1);

use App\Domain\Primary\PrimaryViolation;
use App\Domain\Primary\UnitOrdinals;
use Brick\Math\BigInteger;

it('reserves exactly the requested quantity across gaps without overlapping occupied identities', function (): void {
    $occupied = [['first' => '9', 'last' => '10'], ['first' => '3', 'last' => '5']];
    $selection = UnitOrdinals::reserve('12', $occupied, '6');
    expect($selection->ranges)->toBe([['first' => '1', 'last' => '2'], ['first' => '6', 'last' => '8'], ['first' => '11', 'last' => '11']])
        ->and((string) $selection->count)->toBe('6')
        ->and(UnitOrdinals::reserve('12', [...$occupied, ...$selection->ranges], '1')->ranges)->toBe([['first' => '12', 'last' => '12']]);
    expect(fn () => UnitOrdinals::reserve('12', [...$occupied, ...$selection->ranges], '2'))
        ->toThrow(PrimaryViolation::class, 'INSUFFICIENT_UNITS');
});

it('canonicalizes adjacent ranges and rejects duplicate ownership', function (): void {
    $ordinals = UnitOrdinals::fromRanges('10', [['first' => '4', 'last' => '6'], ['first' => '1', 'last' => '3']]);
    expect($ordinals->ranges)->toBe([['first' => '1', 'last' => '6']])->and((string) $ordinals->count)->toBe('6');
    expect(fn () => UnitOrdinals::fromRanges('10', [['first' => '1', 'last' => '3'], ['first' => '3', 'last' => '6']]))
        ->toThrow(PrimaryViolation::class, 'OVERLAPPING_ORDINALS');
});

it('reuses released identities without changing surviving reservation identities', function (): void {
    $first = UnitOrdinals::reserve('8', [], '3');
    $second = UnitOrdinals::reserve('8', $first->ranges, '2');
    $afterRelease = UnitOrdinals::reserve('8', $second->ranges, '4');
    expect($second->ranges)->toBe([['first' => '4', 'last' => '5']])
        ->and($afterRelease->ranges)->toBe([['first' => '1', 'last' => '3'], ['first' => '6', 'last' => '6']]);
    expect(fn () => UnitOrdinals::reserve('8', [['first' => '1', 'last' => '8']], '1'))
        ->toThrow(PrimaryViolation::class, 'INSUFFICIENT_UNITS');
});

it('keeps ordinal arithmetic exact beyond native integer and javascript limits', function (): void {
    $selection = UnitOrdinals::reserve('100000000000000000000', [['first' => '1', 'last' => '99999999999999999997']], '3');
    expect($selection->ranges)->toBe([['first' => '99999999999999999998', 'last' => '100000000000000000000']])
        ->and((string) $selection->countBetween(BigInteger::of('99999999999999999999'), BigInteger::of('100000000000000000005')))->toBe('2')
        ->and((string) $selection->countBetween(BigInteger::one(), BigInteger::of(10)))->toBe('0');
    expect((string) UnitOrdinals::fromRanges('10', [])->countBetween(BigInteger::one(), BigInteger::of(10)))->toBe('0');
});

it('refuses malformed and zero unit quantities without rounding or increasing them', function (string $quantity): void {
    expect(fn () => UnitOrdinals::reserve('10', [], $quantity))->toThrow(PrimaryViolation::class, 'INVALID_UNITS');
})->with(['0', '-1', '01', '1.5', '1e3', ' 1', '1 ', "1\n", '', str_repeat('9', 65)]);

it('rejects out of bounds and reversed ordinal ranges', function (string $first, string $last): void {
    expect(fn () => UnitOrdinals::fromRanges('10', [['first' => $first, 'last' => $last]]))->toThrow(PrimaryViolation::class, 'INVALID_ORDINALS');
})->with([['2', '1'], ['9', '11']]);

it('matches exhaustive free-unit selection for every small occupied subset', function (): void {
    for ($mask = 0; $mask < 256; $mask++) {
        $occupied = [];
        $available = [];
        for ($ordinal = 1; $ordinal <= 8; $ordinal++) {
            if (($mask & (1 << ($ordinal - 1))) !== 0) {
                $occupied[] = ['first' => (string) $ordinal, 'last' => (string) $ordinal];
            } else {
                $available[] = $ordinal;
            }
        }
        for ($quantity = 1; $quantity <= count($available); $quantity++) {
            $selection = UnitOrdinals::reserve('8', $occupied, (string) $quantity);
            $actual = [];
            foreach ($selection->ranges as $range) {
                $actual = [...$actual, ...range((int) $range['first'], (int) $range['last'])];
            }
            expect($actual)->toBe(array_slice($available, 0, $quantity));
        }
    }
});
