<?php

use App\Enums\PulseSector;
use App\Support\PulseUnderwriting;
use Illuminate\Support\Carbon;

beforeEach(function () {
    Carbon::setTestNow(Carbon::create(2026, 7, 29));
});

afterEach(function () {
    Carbon::setTestNow();
});

test('a strength score is banded and scored out of five', function (float $score, string $band, float $outOfFive) {
    expect(PulseUnderwriting::rating($score))->toBe(['band' => $band, 'score' => $outOfFive]);
})->with([
    'strong' => [90, 'Strong', 4.5],
    'stable' => [68, 'Stable', 3.4],
    'weak' => [45, 'Weak', 2.3],
    'distressed' => [20, 'Distressed', 1.0],
    'no trading history' => [0, 'Distressed', 0.0],
]);

test('a business is scored on its history, its sector and the margin it keeps', function () {
    // 50 + 8 years x 1.5 + logistics 5 + a 0.25 margin x 42.
    expect(PulseUnderwriting::score(120_000_000, 90_000_000, PulseSector::Logistics, 2018))->toBe(77.5);
});

test('the score stays inside the forty to ninety-two band the model allows', function () {
    // Nothing to its name, and everything a business can bring.
    expect(PulseUnderwriting::score(100_000_000, 100_000_000, PulseSector::Other, 2026))->toBe(52.0)
        ->and(PulseUnderwriting::score(100_000_000, 10_000_000, PulseSector::RetailAndTrade, 1990))->toBe(89.9);
});

test('trading history is counted from the year of registration and never runs backwards', function () {
    expect(PulseUnderwriting::yearsTrading(2018))->toBe(8)
        ->and(PulseUnderwriting::yearsTrading(2026))->toBe(0)
        ->and(PulseUnderwriting::yearsTrading(2030))->toBe(0);
});

test('a margin is only a margin while costs stay under revenue', function () {
    expect(PulseUnderwriting::profitMargin(120_000_000, 90_000_000))->toBe(0.25)
        ->and(PulseUnderwriting::profitMargin(90_000_000, 90_000_000))->toBe(0.0)
        ->and(PulseUnderwriting::profitMargin(0, 0))->toBe(0.0);
});

test('the flat rate rises with the term, falls with the score and is held between ten and fifteen', function () {
    expect(PulseUnderwriting::flatRate(12, 77.5))->toBe(13.3)
        ->and(PulseUnderwriting::flatRate(3, 77.5))->toBe(11.8)
        ->and(PulseUnderwriting::flatRate(12, 0.0))->toBe(15.0)
        ->and(PulseUnderwriting::flatRate(3, 100.0))->toBe(10.0);
});

test('a repayment is sized to leave a quarter of the surplus uncommitted', function () {
    // A monthly surplus of RWF 2,500,000 carries a payment of RWF 2,000,000.
    expect(PulseUnderwriting::monthlySurplus(120_000_000, 90_000_000))->toBe(2_500_000.0)
        ->and(PulseUnderwriting::affordablePayment(120_000_000, 90_000_000))->toBe(2_000_000.0)
        ->and(PulseUnderwriting::affordablePayment(90_000_000, 120_000_000))->toBe(0.0);
});

test('the pre-qualified amount follows the worked example the sizing note gives', function () {
    $score = PulseUnderwriting::score(120_000_000, 90_000_000, PulseSector::Logistics, 2018);
    $qualified = PulseUnderwriting::qualifiedAmount(120_000_000, 90_000_000, $score, 12);

    ['monthly' => $monthly] = PulseUnderwriting::repayment(
        $qualified,
        PulseUnderwriting::flatRate(12, $score),
        12
    );

    expect($qualified)->toBe(21_100_000)
        ->and(round($monthly))->toBe(1_992_192.0)
        ->and(round(PulseUnderwriting::coverRatio(120_000_000, 90_000_000, $monthly), 2))->toBe(1.25);
});

test('a pre-qualified amount is rounded down to the nearest hundred thousand', function () {
    $score = PulseUnderwriting::score(120_000_000, 90_000_000, PulseSector::Logistics, 2018);
    $qualified = PulseUnderwriting::qualifiedAmount(120_000_000, 90_000_000, $score, 12);

    // 2,000,000 x 12 / 1.133 is 21,183,583, which floors to 21,100,000.
    expect($qualified % 100_000)->toBe(0)
        ->and($qualified)->toBeLessThan(2_000_000 * 12 / 1.133);
});

test('a loan is capped at a third of the revenue behind it', function () {
    // A fat margin can afford more than the ceiling allows, so the ceiling binds.
    $score = PulseUnderwriting::score(10_000_000, 1_000_000, PulseSector::RetailAndTrade, 2005);

    expect(PulseUnderwriting::qualifiedAmount(10_000_000, 1_000_000, $score, 12))->toBe(3_500_000);
});

test('a business with nothing left over pre-qualifies for nothing', function () {
    expect(PulseUnderwriting::qualifiedAmount(50_000_000, 50_000_000, 60.0, 12))->toBe(0);
});

test('a sizing carries everything a pre-qualification is recorded with', function () {
    expect(PulseUnderwriting::size(120_000_000, 90_000_000, PulseSector::Logistics, 2018, 12))->toBe([
        'annual_revenue' => 120_000_000,
        'annual_costs' => 90_000_000,
        'sector' => PulseSector::Logistics,
        'registered_year' => 2018,
        'score' => 77.5,
        'qualified_amount' => 21_100_000,
        'term_months' => 12,
        'flat_rate' => 13.3,
        'rating_band' => 'Stable',
        'rating_score' => 3.9,
    ]);
});

test('an investor is projected the blended yield on top of their pledge', function () {
    expect(PulseUnderwriting::projectedReturn(500_000))->toBe(562_500)
        ->and(PulseUnderwriting::projectedReturn(5_000))->toBe(5_625);
});
