<?php

declare(strict_types=1);

use App\Domain\Business\ReadinessStage;
use Brick\Math\BigInteger;

/**
 * @param  list<string>  $revenues  oldest first, one per month from January 2026
 * @return list<array{period: string, revenue: string}>
 */
$months = fn (array $revenues): array => array_map(fn (string $revenue, int $index): array => ['period' => sprintf('2026-%02d', $index + 1),
    'revenue' => $revenue], $revenues, array_keys($revenues));

it('waits for 3 sealed reports before naming a stage', function () use ($months): void {
    expect((new ReadinessStage)->assess($months(['12000000', '12000000'])))->toBe(['vmr' => null, 'stage' => null, 'indicated' => null,
        'months_pointing' => 0, 'reports' => 2, 'reports_needed' => 1, 'window' => 3, 'next' => null,
        'thresholds' => [['stage' => 1, 'from' => '2500000'], ['stage' => 2, 'from' => '10000000'], ['stage' => 3, 'from' => '30000000'], ['stage' => 4, 'from' => '80000000']],
        'months' => [['period' => '2026-02', 'revenue' => '12000000'], ['period' => '2026-01', 'revenue' => '12000000']]])
        ->and((new ReadinessStage)->assess([]))->toMatchArray(['stage' => null, 'reports' => 0, 'reports_needed' => 3, 'months' => []]);
});

it('sets the first stage from the average of the last 3 reports, rounded down to whole francs', function () use ($months): void {
    $stage = (new ReadinessStage)->assess(array_reverse($months(['10000000', '10000000', '9999999'])));

    expect($stage)->toMatchArray(['vmr' => '9999999', 'stage' => 1, 'indicated' => 1, 'months_pointing' => 0, 'reports' => 3, 'reports_needed' => 0,
        'next' => ['stage' => 2, 'threshold' => '10000000']])
        ->and($stage['months'])->toBe([['period' => '2026-03', 'revenue' => '9999999'], ['period' => '2026-02', 'revenue' => '10000000'],
            ['period' => '2026-01', 'revenue' => '10000000']]);
});

it('places each reading in its stage, with stage 0 below Foundation', function (string $vmr, int $stage): void {
    expect(ReadinessStage::stageFor(BigInteger::of($vmr)))->toBe($stage);
})->with([['2499999', 0], ['2500000', 1], ['9999999', 1], ['10000000', 2], ['29999999', 2], ['30000000', 3], ['79999999', 3], ['80000000', 4]]);

it('never moves the stage on one month above or below a threshold', function () use ($months): void {
    // One 90M month lifts all 3 averages it is part of to 38M (Scale), but it is the only Scale month among them.
    $spike = (new ReadinessStage)->assess($months(['12000000', '12000000', '12000000', '90000000', '12000000', '12000000']));
    expect($spike)->toMatchArray(['vmr' => '38000000', 'stage' => 2, 'indicated' => 3, 'months_pointing' => 3])
        ->and((new ReadinessStage)->assess($months(['12000000', '12000000', '12000000', '90000000', '12000000', '12000000', '12000000'])))
        ->toMatchArray(['vmr' => '12000000', 'stage' => 2, 'indicated' => 2, 'months_pointing' => 0]);

    // One empty month pulls 3 averages to 26.67M (Growth), but the other months stay in Scale.
    expect((new ReadinessStage)->assess($months(['40000000', '40000000', '40000000', '0', '40000000', '40000000'])))
        ->toMatchArray(['vmr' => '26666666', 'stage' => 3, 'indicated' => 2, 'months_pointing' => 3]);
});

it('moves up after 3 consecutive readings above the threshold, to the nearest stage they share', function () use ($months): void {
    $stage = (new ReadinessStage)->assess($months(['12000000', '12000000', '12000000', '35000000', '35000000', '35000000', '90000000']));

    // Readings: 12M, 19.67M, 27.33M, 35M, 53.33M → Growth ×3, then Scale ×3 once the third Scale reading lands.
    expect($stage)->toMatchArray(['vmr' => '53333333', 'stage' => 2, 'indicated' => 3, 'months_pointing' => 2]);
    expect((new ReadinessStage)->assess($months(['12000000', '12000000', '12000000', '35000000', '35000000', '35000000', '90000000', '90000000'])))
        ->toMatchArray(['vmr' => '71666666', 'stage' => 3, 'indicated' => 3, 'months_pointing' => 0, 'next' => ['stage' => 4, 'threshold' => '80000000']]);
});

it('moves down only after 3 consecutive readings below the threshold', function () use ($months): void {
    $falling = ['40000000', '40000000', '40000000', '20000000', '20000000', '20000000'];
    expect((new ReadinessStage)->assess($months($falling)))->toMatchArray(['vmr' => '20000000', 'stage' => 3, 'indicated' => 2, 'months_pointing' => 2])
        ->and((new ReadinessStage)->assess($months([...$falling, '20000000'])))->toMatchArray(['stage' => 2, 'months_pointing' => 0])
        ->and((new ReadinessStage)->assess($months(['1000000', '1000000', '1000000'])))->toMatchArray(['stage' => 0, 'next' => ['stage' => 1, 'threshold' => '2500000']]);
});

it('keeps the stage when readings point away in opposite directions, and names no stage above Market', function () use ($months): void {
    // Readings: 20M Growth, 36.67M and 30M Scale, 23.33M Growth, then 0 below Foundation: never 3 on one side.
    expect((new ReadinessStage)->assess($months(['20000000', '20000000', '20000000', '70000000', '0', '0', '0'])))
        ->toMatchArray(['vmr' => '0', 'stage' => 2, 'indicated' => 0, 'months_pointing' => 1])
        ->and((new ReadinessStage)->assess($months(['90000000', '90000000', '90000000'])))->toMatchArray(['stage' => 4, 'next' => null]);
});
