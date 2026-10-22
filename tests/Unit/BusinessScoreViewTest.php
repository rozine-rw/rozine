<?php

declare(strict_types=1);

use App\Domain\Underwriting\BusinessScoreView;
use App\Domain\Underwriting\LoanCapacity;

$ratio = fn (string $numerator, string $denominator = '1'): array => ['numerator' => $numerator, 'denominator' => $denominator];
$money = fn (string $amount): array => ['currency' => 'RWF', 'amount' => $amount];

it('groups the engine components into four weighted factors out of 100', function () use ($ratio): void {
    $factors = BusinessScoreView::factors(['coverage' => $ratio('80'), 'cfads_margin' => $ratio('55'), 'conduct' => $ratio('50'),
        'nocf_stability' => $ratio('181', '2'), 'positive_months' => $ratio('100'), 'history_depth' => $ratio('100', '3')]);

    // (80×30 + 55×20) ÷ 50 = 70; (90.5×20 + 100×10) ÷ 30 = 93.67; 33.33 rounds to 33.
    expect($factors)->toBe([['key' => 'financial_health', 'score' => 70], ['key' => 'repayment_history', 'score' => 50],
        ['key' => 'statement_consistency', 'score' => 94], ['key' => 'track_record', 'score' => 33]]);
});

it('sizes capacity on the engine offer at the lowest limit, less commitments accepted since the quote', function () use ($ratio, $money): void {
    $sizing = (new BusinessScoreView(new LoanCapacity))->sizing(['cfads' => $ratio('1000000'), 'exposure_limit' => $ratio('10000000'),
        'committed_exposure' => $ratio('2000000'), 'remaining_room' => $ratio('8000000'), 'repeat_projections' => []],
        ['percent' => '13.0', 'fraction' => $ratio('13', '100')], $ratio('400000000', '113'), 6, [], [['id' => 'own', 'principal' => '1000000']]);

    expect($sizing)->toBe(['cash_per_month' => $money('1000000'), 'target_cover' => '1.50', 'term_months' => 6, 'rate_percent' => '13.0',
        'limits' => [['key' => 'cash_flow', 'value' => $money('3539823'), 'binding' => true],
            ['key' => 'exposure', 'value' => $money('8000000'), 'binding' => false, 'limit' => $money('10000000'), 'committed' => $money('2000000')],
            ['key' => 'policy_max', 'value' => $money('100000000'), 'binding' => false]],
        'approved' => $money('3535000'), 'headroom' => $money('2535000')]);
});

it('offers nothing below the minimum principal, and never a negative limit', function () use ($ratio, $money): void {
    $scores = new BusinessScoreView(new LoanCapacity);
    $room = $scores->sizing(['cfads' => $ratio('1000000'), 'exposure_limit' => $ratio('5000000'), 'committed_exposure' => $ratio('2500000'),
        'remaining_room' => $ratio('2500000'), 'repeat_projections' => []], ['percent' => '13.0', 'fraction' => $ratio('13', '100')],
        $ratio('400000000', '113'), 6, [], []);
    $cash = $scores->sizing(['cfads' => $ratio('-1000'), 'exposure_limit' => $ratio('5000000'), 'committed_exposure' => $ratio('0'),
        'remaining_room' => $ratio('5000000'), 'repeat_projections' => []], ['percent' => '13.0', 'fraction' => $ratio('13', '100')],
        $ratio('-4000', '1'), 6, [], []);

    expect(array_column($room['limits'], 'binding'))->toBe([false, true, false])
        ->and($room['approved'])->toBe($money('0'))->and($room['headroom'])->toBe($money('0'))
        ->and($cash['limits'][0])->toBe(['key' => 'cash_flow', 'value' => $money('0'), 'binding' => true])
        ->and($cash['cash_per_month'])->toBe($money('-1000'))->and($cash['approved'])->toBe($money('0'));
});

it('deducts a repeat raise\'s earlier funding once, since the newer quote already counted it', function () use ($ratio, $money): void {
    // RWF 20M exposure limit less RWF 6M of earlier funding leaves RWF 14M, all of it offered.
    $sizing = fn (array $current): array => (new BusinessScoreView(new LoanCapacity))->sizing(['cfads' => $ratio('10000000'),
        'exposure_limit' => $ratio('20000000'), 'committed_exposure' => $ratio('6000000'), 'remaining_room' => $ratio('14000000'),
        'repeat_projections' => []], ['percent' => '13.0', 'fraction' => $ratio('13', '100')], $ratio('50000000'), 6,
        [['id' => 'first', 'principal' => '6000000']], $current);

    $listed = $sizing([['id' => 'first', 'principal' => '6000000']]);
    $funded = $sizing([['id' => 'first', 'principal' => '6000000'], ['id' => 'second', 'principal' => '3000000']]);
    // A quoted commitment released since is not given back.
    $released = $sizing([['id' => 'second', 'principal' => '3000000']]);

    expect($listed['limits'][1]['binding'])->toBeTrue()->and($listed['approved'])->toBe($money('14000000'))
        ->and($listed['headroom'])->toBe($money('14000000'))
        ->and($funded['headroom'])->toBe($money('11000000'))
        ->and($released['headroom'])->toBe($money('11000000'));
});
