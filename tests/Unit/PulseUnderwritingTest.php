<?php

use App\Support\PulseUnderwriting;

test('a strength score is banded and scored out of five', function (int $score, string $band, float $outOfFive) {
    expect(PulseUnderwriting::rating($score))->toBe(['band' => $band, 'score' => $outOfFive]);
})->with([
    'strong' => [90, 'Strong', 4.5],
    'stable' => [68, 'Stable', 3.4],
    'weak' => [45, 'Weak', 2.3],
    'distressed' => [20, 'Distressed', 1.0],
    'no trading history' => [0, 'Distressed', 0.0],
]);

test('the flat rate rises with the term and is held between ten and fifteen', function () {
    expect(PulseUnderwriting::flatRate(3))->toBe(12.6)
        ->and(PulseUnderwriting::flatRate(6))->toBe(13.1)
        ->and(PulseUnderwriting::flatRate(9))->toBe(13.6)
        ->and(PulseUnderwriting::flatRate(12))->toBe(14.1)
        ->and(PulseUnderwriting::flatRate(12, 0))->toBe(15.0)
        ->and(PulseUnderwriting::flatRate(3, 100))->toBe(10.0);
});

test('capacity is sized off a multiple of annual inflow', function () {
    $qualified = PulseUnderwriting::qualifiedAmount(100_000_000, 12);

    expect($qualified)->toBe((int) round((2.75 * 100_000_000 * 12) / (24 * 1.141)))
        ->and(PulseUnderwriting::qualifiedAmount(0, 12))->toBe(0);
});

test('a sizing carries everything a pre-qualification is recorded with', function () {
    expect(PulseUnderwriting::size(75_000_000, 6))->toBe([
        'annual_inflow' => 75_000_000,
        'qualified_amount' => PulseUnderwriting::qualifiedAmount(75_000_000, 6),
        'term_months' => 6,
        'flat_rate' => 13.1,
        'rating_band' => 'Stable',
        'rating_score' => 3.4,
    ]);
});

test('an investor is projected the blended yield on top of their pledge', function () {
    expect(PulseUnderwriting::projectedReturn(500_000))->toBe(562_500)
        ->and(PulseUnderwriting::projectedReturn(5_000))->toBe(5_625);
});

test('the inflow read off a statement lands in the range the model expects', function () {
    $inflow = PulseUnderwriting::estimateAnnualInflow();

    expect($inflow)->toBeGreaterThanOrEqual(48_000_000)
        ->toBeLessThanOrEqual(127_000_000)
        ->and($inflow % 1_000_000)->toBe(0);
});
