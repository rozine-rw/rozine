<?php

declare(strict_types=1);

use App\Domain\Auditor\AuditVariance;

it('keeps exact differences separate from rounded percentages under zero tolerance', function (?string $reported, mixed $observed, ?string $percentage, ?bool $within): void {
    expect((new AuditVariance)->compare($reported, $observed))->toBe($percentage === null ? null : ['pct' => $percentage, 'within' => $within]);
})->with([
    ['1000', '1000', '0.0', true],
    ['1000', '875', '-12.5', false],
    ['1000', '1125', '12.5', false],
    ['1000', '0', '-100.0', false],
    ['10000000', '10000001', '0.0', false],
    ['9007199254740993', '9007199254740992', '0.0', false],
    ['0', '0', '0.0', true],
    ['0', '1', null, null],
    [null, '1', null, null],
    ['1', null, null, null],
    ['1', 1, null, null],
    ['1', [], null, null],
    ['1', '', null, null],
    ['1', '01', null, null],
    ['1', '-1', null, null],
    ['1', '1e9', null, null],
]);

it('weighs a sealed report by its absolute reported money, ignoring other measures', function (array $findings, ?string $weighted): void {
    expect(AuditVariance::weighted($findings))->toBe($weighted);
})->with([
    [[['measure' => 'stock_value', 'reported' => '1000', 'observed' => '900'], ['measure' => 'cash', 'reported' => '1000', 'observed' => '1100']], '10.0'],
    [[['measure' => 'stock_value', 'reported' => '1000', 'observed' => '1000'], ['measure' => 'cash', 'reported' => '-1000', 'observed' => '-900']], '5.0'],
    [[['measure' => 'stock_units', 'reported' => '10', 'observed' => '5']], null],
    [[['measure' => 'cash', 'reported' => '0', 'observed' => '0']], null],
    [[], null],
]);

it('detects exact money differences independently of the rounded percentage', function (array $findings, ?string $weighted, bool $flagged): void {
    expect(AuditVariance::weighted($findings))->toBe($weighted)
        ->and(AuditVariance::hasMoneyDifference($findings))->toBe($flagged);
})->with([
    [[['measure' => 'cash', 'reported' => '1000000', 'observed' => '1000001']], '0.0', true],
    [[['measure' => 'stock_value', 'reported' => '1000000', 'observed' => '999999']], '0.0', true],
    [[['measure' => 'cash', 'reported' => '-1000000', 'observed' => '-1000001']], '0.0', true],
    [[['measure' => 'cash', 'reported' => '-1000000', 'observed' => '-999999']], '0.0', true],
    [[['measure' => 'cash', 'reported' => '9007199254740993', 'observed' => '9007199254740994']], '0.0', true],
    [[['measure' => 'cash', 'reported' => '1000000', 'observed' => '1000000']], '0.0', false],
    [[['measure' => 'cash', 'reported' => '0', 'observed' => '0']], null, false],
    [[['measure' => 'cash', 'reported' => '0', 'observed' => '1']], null, true],
    [[['measure' => 'cash', 'reported' => null, 'observed' => '1']], null, false],
    [[['measure' => 'cash', 'reported' => '1', 'observed' => null]], null, false],
    [[['measure' => 'stock_units', 'reported' => '10', 'observed' => '11']], null, false],
    [[], null, false],
]);
