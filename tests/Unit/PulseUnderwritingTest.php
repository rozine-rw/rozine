<?php

declare(strict_types=1);

use App\Domain\Pulse\PulseSector;
use App\Domain\Pulse\PulseUnderwriting;

test('a strength score is banded and scored out of five', function (float $score, string $band, float $outOfFive) {
    expect(PulseUnderwriting::rating($score))->toBe(['band' => $band, 'score' => $outOfFive]);
})->with([
    'strong' => [90, 'Strong', 4.5],
    'stable' => [68, 'Stable', 3.4],
    'weak' => [45, 'Weak', 2.3],
    'distressed' => [20, 'Distressed', 1.0],
    'no trading history' => [0, 'Distressed', 0.0],
]);

test('every sector contributes its approved strength score', function (PulseSector $sector, int $score) {
    expect($sector->score())->toBe($score);
})->with([
    'agriculture' => [PulseSector::Agriculture, 4],
    'retail and trade' => [PulseSector::RetailAndTrade, 6],
    'logistics' => [PulseSector::Logistics, 5],
    'manufacturing' => [PulseSector::Manufacturing, 4],
    'services' => [PulseSector::Services, 5],
    'energy' => [PulseSector::Energy, 3],
    'other' => [PulseSector::Other, 2],
]);

test('a business is scored on its history, its sector and the margin it keeps', function () {
    expect(PulseUnderwriting::score(120_000_000, 90_000_000, PulseSector::Logistics, 2018, 2026))->toBe(77.5);
});

test('the score stays inside the band the simulation allows', function () {
    expect(PulseUnderwriting::score(100_000_000, 100_000_000, PulseSector::Other, 2026, 2026))->toBe(52.0)
        ->and(PulseUnderwriting::score(100_000_000, 10_000_000, PulseSector::RetailAndTrade, 1990, 2026))->toBe(89.9);
});

test('trading history uses the supplied year and never runs backwards', function () {
    expect(PulseUnderwriting::yearsTrading(2018, 2026))->toBe(8)
        ->and(PulseUnderwriting::yearsTrading(2026, 2026))->toBe(0)
        ->and(PulseUnderwriting::yearsTrading(2030, 2026))->toBe(0);
});

test('a margin is only a margin while costs stay under revenue', function () {
    expect(PulseUnderwriting::profitMargin(120_000_000, 90_000_000))->toBe(0.25)
        ->and(PulseUnderwriting::profitMargin(90_000_000, 90_000_000))->toBe(0.0)
        ->and(PulseUnderwriting::profitMargin(0, 0))->toBe(0.0);
});

test('the flat rate rises with the term and is held inside its limits', function () {
    expect(PulseUnderwriting::flatRate(12, 77.5))->toBe(14.4125)
        ->and(PulseUnderwriting::flatRate(3, 77.5))->toBe(11.9125)
        ->and(PulseUnderwriting::flatRate(12, 0.0))->toBe(15.0)
        ->and(PulseUnderwriting::flatRate(3, 100.0))->toBe(10.5);
});

test('a repayment is sized to leave a quarter of the surplus uncommitted', function () {
    expect(PulseUnderwriting::monthlySurplus(120_000_000, 90_000_000))->toBe(2_500_000.0)
        ->and(PulseUnderwriting::affordablePayment(120_000_000, 90_000_000))->toBe(2_000_000.0)
        ->and(PulseUnderwriting::affordablePayment(90_000_000, 120_000_000))->toBe(0.0);
});

test('the pre-qualified amount is what the surplus carries over the term', function () {
    $score = PulseUnderwriting::score(120_000_000, 90_000_000, PulseSector::Logistics, 2018, 2026);
    $qualified = PulseUnderwriting::qualifiedAmount(120_000_000, 90_000_000, $score, 12);

    ['monthly' => $monthly] = PulseUnderwriting::repayment(
        $qualified,
        PulseUnderwriting::flatRate(12, $score),
        12,
    );

    expect($qualified)->toBe(20_900_000)
        ->and(round($monthly))->toBe(1_992_684.0)
        ->and(round(PulseUnderwriting::coverRatio(120_000_000, 90_000_000, $monthly), 2))->toBe(1.25);
});

test('a pre-qualified amount is rounded down to the nearest hundred thousand', function () {
    $score = PulseUnderwriting::score(120_000_000, 90_000_000, PulseSector::Logistics, 2018, 2026);
    $qualified = PulseUnderwriting::qualifiedAmount(120_000_000, 90_000_000, $score, 12);

    expect($qualified % 100_000)->toBe(0)
        ->and($qualified)->toBeLessThan(2_000_000 * 12 / 1.144125);
});

test('a loan is capped at a third of the revenue behind it', function () {
    $score = PulseUnderwriting::score(10_000_000, 1_000_000, PulseSector::RetailAndTrade, 2005, 2026);

    expect(PulseUnderwriting::qualifiedAmount(10_000_000, 1_000_000, $score, 12))->toBe(3_500_000);
});

test('a non-positive monthly repayment has no cover', function () {
    expect(PulseUnderwriting::coverRatio(120_000_000, 90_000_000, 0))->toBe(0.0)
        ->and(PulseUnderwriting::coverRatio(120_000_000, 90_000_000, -1))->toBe(0.0);
});

test('a business with nothing left over pre-qualifies for nothing', function () {
    expect(PulseUnderwriting::qualifiedAmount(50_000_000, 50_000_000, 60.0, 12))->toBe(0);
});

test('a loan never exceeds the largest Rozine writes', function () {
    $score = PulseUnderwriting::score(900_000_000, 300_000_000, PulseSector::RetailAndTrade, 2005, 2026);

    expect(PulseUnderwriting::sizedAmount(900_000_000, 300_000_000, $score, 12))
        ->toBeGreaterThan(PulseUnderwriting::MAX_LOAN)
        ->and(PulseUnderwriting::qualifiedAmount(900_000_000, 300_000_000, $score, 12))
        ->toBe(PulseUnderwriting::MAX_LOAN);
});

test('the surplus the smallest loan needs falls as the term lengthens', function () {
    $score = PulseUnderwriting::score(120_000_000, 90_000_000, PulseSector::Logistics, 2018, 2026);

    $overThree = PulseUnderwriting::surplusForMinimumLoan($score, 3);
    $overTwelve = PulseUnderwriting::surplusForMinimumLoan($score, 12);

    expect(round($overTwelve))->toBe(357_539.0)
        ->and($overThree)->toBeGreaterThan($overTwelve);
});

test('a sizing carries persisted and presentation facts from one calculation', function () {
    expect(PulseUnderwriting::size(
        120_000_000,
        90_000_000,
        PulseSector::Logistics,
        2018,
        12,
        2026,
    ))->toBe([
        'annual_revenue' => 120_000_000,
        'annual_costs' => 90_000_000,
        'sector' => PulseSector::Logistics,
        'registered_year' => 2018,
        'score' => 77.5,
        'qualified_amount' => 20_900_000,
        'term_months' => 12,
        'flat_rate' => 14.41,
        'rating_band' => 'Stable',
        'rating_score' => 3.9,
        'rating' => ['band' => 'Stable', 'score' => 3.9],
        'sized_amount' => 20_900_000,
        'monthly_repayment' => 1_992_684.38,
        'monthly_surplus' => 2_500_000.0,
        'cover_ratio' => 1.25,
        'below_minimum' => false,
        'at_maximum' => false,
        'required_surplus' => 357_539.06,
        'status' => 'pre_qualified',
    ]);
});

test('sizing identifies both waitlisted and maximum-capped simulations', function () {
    $waitlisted = PulseUnderwriting::size(16_000_000, 15_400_000, PulseSector::Other, 2024, 3, 2026);
    $capped = PulseUnderwriting::size(900_000_000, 300_000_000, PulseSector::RetailAndTrade, 2005, 12, 2026);

    expect($waitlisted['below_minimum'])->toBeTrue()
        ->and($waitlisted['status'])->toBe('waitlisted')
        ->and($capped['at_maximum'])->toBeTrue()
        ->and($capped['qualified_amount'])->toBe(PulseUnderwriting::MAX_LOAN);
});

test('pledges are canonicalized to the approved band and step', function (int $given, int $expected) {
    expect(PulseUnderwriting::normalisePledge($given))->toBe($expected);
})->with([
    'below minimum' => [0, 5_000],
    'minimum' => [5_000, 5_000],
    'round down' => [7_499, 5_000],
    'half step rounds up' => [7_500, 10_000],
    'above maximum' => [200_000_001, 200_000_000],
]);

test('an investor projection uses the supplied yield', function () {
    expect(PulseUnderwriting::projectedReturn(500_000))->toBe(565_000)
        ->and(PulseUnderwriting::projectedReturnAtRate(500_000, 14.1))->toBe(570_500);
});

test('policy options are produced by the domain', function () {
    $policy = PulseUnderwriting::policy(2026);

    expect($policy['terms'])->toBe([3, 4, 5, 6, 9, 12])
        ->and($policy['sectors'])->toBe([
            'Agriculture',
            'Retail & trade',
            'Logistics',
            'Manufacturing',
            'Services',
            'Energy',
            'Other',
        ])
        ->and($policy['registration_years'])->toHaveCount(31)
        ->and($policy['registration_years'][0])->toBe(2026)
        ->and($policy['registration_years'][30])->toBe(1996)
        ->and($policy['pledge'])->toBe([
            'minimum' => 5_000,
            'maximum' => 200_000_000,
            'step' => 5_000,
            'default' => 500_000,
        ]);
});
