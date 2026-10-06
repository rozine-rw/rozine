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
