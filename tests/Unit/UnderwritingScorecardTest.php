<?php

declare(strict_types=1);

use App\Domain\Underwriting\EngineScorecard;
use Brick\Math\BigRational;

it('reproduces the approved scorecard vectors without intermediate rounding', function (string $coverage, string $margin, string $mad, int $positive, string $score, string $rating, string $band): void {
    $result = (new EngineScorecard)->evaluate(BigRational::of($coverage), BigRational::of($margin), BigRational::of($mad), $positive, 12, false);
    expect($result['rating'])->toBe($rating)->and($result['band'])->toBe($band)
        ->and(BigRational::ofFraction($result['score']['numerator'], $result['score']['denominator'])->isEqualTo($score))->toBeTrue();
})->with([
    ['1.50', '.25', '.10', 12, '90', '4.5', 'Strong'],
    ['1.40', '.20', '.15', 12, '77', '3.9', 'Stable'],
    ['1.30', '.15', '.25', 10, '176/3', '2.9', 'Weak'],
]);

it('applies the worst adverse conduct cap without increasing a lower score', function (bool $arrears, bool $breach, bool $defaulted, int $dpd, string $rating): void {
    $result = (new EngineScorecard)->evaluate(BigRational::of('3/2'), BigRational::of('1/4'), BigRational::of('1/10'), 12, 12, false, postGraceArrears: $arrears, reportingBreach: $breach, defaulted: $defaulted, daysPastDue: $dpd);
    expect($result['rating'])->toBe($rating)->and($result['uncapped_score'])->toBe(['numerator' => '90', 'denominator' => '1']);
})->with([
    [true, false, false, 0, '2.9'], [false, true, false, 0, '2.9'],
    [true, true, true, 0, '1.9'], [false, false, false, 30, '1.9'],
    [false, false, false, 29, '4.5'],
]);

it('uses actual conduct ratios after history exists and clamps normalization only', function (): void {
    $result = (new EngineScorecard)->evaluate(BigRational::of(2), BigRational::of(1), BigRational::zero(), 36, 36, true, ['on_time' => 2, 'total' => 3], ['on_time' => 1, 'total' => 2]);
    expect($result['components']['conduct'])->toBe(['numerator' => '185', 'denominator' => '3'])
        ->and($result['score'])->toBe(['numerator' => '377', 'denominator' => '4'])
        ->and($result['rating'])->toBe('4.7');
    $poor = (new EngineScorecard)->evaluate(BigRational::of('.5'), BigRational::of('-1'), BigRational::of(1), 0, 6, true, ['on_time' => 0, 'total' => 1], ['on_time' => 0, 'total' => 1], defaulted: true);
    expect($poor['rating'])->toBe('0.0')->and($poor['band'])->toBe('Distressed')->and($poor['score']['numerator'])->toBe('0');
});

it('returns Unrated for missing mandatory factors or invalid observation counts', function (int $missing, int $positive, int $months, int $dpd, string $mad): void {
    $result = (new EngineScorecard)->evaluate($missing === 1 ? null : BigRational::of(1), $missing === 2 ? null : BigRational::of('.2'), $missing === 3 ? null : BigRational::of($mad), $positive, $months, false, daysPastDue: $dpd);
    expect($result['rating'])->toBeNull()->and($result['score'])->toBeNull()->and($result['band'])->toBe('Unrated');
})->with([
    [1, 6, 12, 0, '.1'], [2, 6, 12, 0, '.1'], [3, 6, 12, 0, '.1'],
    [0, 3, 5, 0, '.1'], [0, -1, 12, 0, '.1'], [0, 13, 12, 0, '.1'],
    [0, 6, 12, -1, '.1'], [0, 6, 12, 0, '-.1'],
]);

it('does not substitute the new-business neutral for missing or invalid existing conduct', function (?int $onTime, int $total): void {
    $record = $onTime === null ? null : ['on_time' => $onTime, 'total' => $total];
    $result = (new EngineScorecard)->evaluate(BigRational::of(1), BigRational::of('.2'), BigRational::of('.1'), 12, 12, true, $record, ['on_time' => 1, 'total' => 1]);
    expect($result['rating'])->toBeNull()->and($result['reason_codes'])->toBe(['CONDUCT_EVIDENCE_REQUIRED']);
})->with([[null, 1], [0, 0], [-1, 1], [2, 1]]);
