<?php

declare(strict_types=1);

use App\Http\Resources\AuditorJobsResource;

it('groups whole-number finding figures for reading and leaves anything else as it is', function (mixed $value, mixed $shown): void {
    expect(AuditorJobsResource::figures(['observed' => $value]))->toBe(['observed' => $shown]);
})->with([
    ['38000000', '38,000,000'],
    ['-1600000', '-1,600,000'],
    ['108000001', '108,000,001'],
    ['190', '190'],
    ['1000', '1,000'],
    ['0', '0'],
    ['-5', '-5'],
    ['9007199254740993', '9,007,199,254,740,993'],
    ['active', 'active'],
    ['12.5', '12.5'],
    ['', ''],
    [42, 42],
]);

it('keeps each value under its own key', function (): void {
    expect(AuditorJobsResource::figures(['reported' => '38000000', 'observed' => '37000000', 'difference' => '-1000000']))
        ->toBe(['reported' => '38,000,000', 'observed' => '37,000,000', 'difference' => '-1,000,000']);
});
