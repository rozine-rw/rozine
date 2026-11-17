<?php

declare(strict_types=1);

use App\Domain\Underwriting\ApplicationUnderwriting;
use App\Domain\Underwriting\LoanCapacity;
use App\Domain\Underwriting\LoanSchedule;
use App\Domain\Underwriting\UnderwritingViolation;
use Brick\Math\BigRational;

it('uses exactly one affordability threshold in every current loan path', function (): void {
    expect(LoanCapacity::TARGET_COVERAGE)->toBe('3/2')
        ->and(LoanCapacity::MINIMUM_COVERAGE)->toBe(LoanCapacity::TARGET_COVERAGE)
        ->and(LoanCapacity::REPEAT_MONTHLY_COVERAGE)->toBe(LoanCapacity::TARGET_COVERAGE)
        ->and(LoanCapacity::CALCULATOR_VERSION)->not->toBe('underwriting-1')
        ->and(ApplicationUnderwriting::VERSION)->not->toBe('application-underwriting-2');
});

it('rejects the former counteroffer band without a lower acceptance bypass', function (string $cfads): void {
    $result = (new LoanCapacity)->evaluate('3000000', 3, BigRational::of('1/10'), BigRational::of($cfads), BigRational::of(100000000), acceptedPrincipal: '3000000');
    expect($result['eligible'])->toBeFalse()->and($result['code'])->toBe('DSCR_BELOW_CUTOFF')
        ->and($result['offer'])->toBeNull()->and($result['raw_capacity'])->toBeNull();
})->with(['1375000', '1485000', '16499999/10']);

it('rejects the 30M request and separately plans the confirmed 24.56M capacity', function (): void {
    $calculator = new LoanCapacity;
    $rate = BigRational::of('7/50');
    $cfads = BigRational::of(7000000);
    $room = BigRational::of(30000000);
    $rejected = $calculator->evaluate('30000000', 6, $rate, $cfads, $room, acceptedPrincipal: '24560000');
    expect($rejected['eligible'])->toBeFalse()->and($rejected['offer'])->toBeNull();
    $plan = $calculator->plan(6, $rate, $cfads, $room);
    expect($plan['principal']['amount'])->toBe('24560000');
    $fresh = $calculator->evaluate('24560000', 6, $rate, $cfads, $room);
    expect($fresh['eligible'])->toBeTrue()->and($fresh['offer'])->toBe($plan);
    $next = LoanSchedule::build('24565000', 6, $rate);
    expect($next->total->multipliedBy(3)->isGreaterThan($cfads->multipliedBy(12)))->toBeTrue();
});

it('checks exact repeat monthly coverage on the original schedule before caps or acceptance', function (string $last, bool $eligible): void {
    $months = [BigRational::of(1700000), BigRational::of(1700000), BigRational::of($last)];
    $result = (new LoanCapacity)->evaluate('3000000', 3, BigRational::of('1/10'), BigRational::of(1700000), BigRational::of(100000000), $months);
    expect($result['eligible'])->toBe($eligible);
    if (! $eligible) {
        expect($result['offer'])->toBeNull()->and($result['code'])->toBe('DSCR_BELOW_CUTOFF');
    }
})->with([['16499999/10', false], ['1650000', true], ['16500001/10', true]]);

it('returns no informational plan below the minimum or with a negative repeat month', function (): void {
    $calculator = new LoanCapacity;
    expect($calculator->plan(6, BigRational::of('7/50'), BigRational::of(100000), BigRational::of(100000000)))->toBeNull()
        ->and($calculator->plan(6, BigRational::of('7/50'), BigRational::of(7000000), BigRational::of(-1)))->toBeNull()
        ->and($calculator->plan(3, BigRational::of('1/10'), BigRational::of(7000000), BigRational::of(100000000), [BigRational::of(7000000), BigRational::of(7000000), BigRational::of(-1)]))->toBeNull();
    expect(fn () => $calculator->plan(3, BigRational::of('1/10'), BigRational::of(7000000), BigRational::of(100000000), [BigRational::of(7000000)]))
        ->toThrow(UnderwritingViolation::class, 'UNDERWRITING_EVIDENCE_REQUIRED');
});

it('steps an informational plan down when the final rounded instalment crosses 1.50', function (bool $repeat): void {
    $calculator = new LoanCapacity;
    $rate = BigRational::of('111/1000');
    $cfads = BigRational::of(838805);
    $monthly = $repeat ? array_fill(0, 6, $cfads) : [];
    $original = $calculator->evaluate('3020000', 6, $rate, $cfads, BigRational::of(100000000), $monthly, acceptedPrincipal: '3000000');
    expect($original['original_dscr'])->toBe(['numerator' => '3', 'denominator' => '2'])
        ->and($original['code'])->toBe('DSCR_BELOW_CUTOFF')->and($original['offer'])->toBeNull();
    $plan = $calculator->plan(6, $rate, $cfads, BigRational::of(100000000), $monthly);
    expect($plan['principal']['amount'])->toBe('3015000');
    foreach ($plan['instalments'] as $instalment) {
        expect($cfads->multipliedBy(2)->isGreaterThanOrEqualTo(BigRational::of($instalment['amount'])->multipliedBy(3)))->toBeTrue();
    }
})->with([false, true]);

it('does not understate a repeat plan when a regular instalment rounds down', function (): void {
    $monthly = array_fill(0, 6, BigRational::of(900000));
    $monthly[0] = BigRational::of('1677609/2');
    $plan = (new LoanCapacity)->plan(6, BigRational::of('111/1000'), BigRational::of(900000), BigRational::of(100000000), $monthly);
    expect($plan['principal']['amount'])->toBe('3020000');
    foreach ($plan['instalments'] as $index => $instalment) {
        expect($monthly[$index]->multipliedBy(2)->isGreaterThanOrEqualTo(BigRational::of($instalment['amount'])->multipliedBy(3)))->toBeTrue();
    }
});

it('compares a first-time final-payment boundary without rounding the coverage ratio', function (string $cfads, bool $eligible): void {
    $result = (new LoanCapacity)->evaluate('3020000', 6, BigRational::of('111/1000'), BigRational::of($cfads), BigRational::of(100000000));
    expect($result['eligible'])->toBe($eligible);
})->with([['8388074/10', false], ['1677615/2', true], ['8388076/10', true]]);

it('rechecks actual offered payments after quantizing a non-grid original request', function (): void {
    $original = LoanSchedule::build('3020001', 6, BigRational::of('111/1000'));
    $cfads = BigRational::of(838806);
    foreach ($original->instalments as $payment) {
        expect($cfads->multipliedBy(2)->isGreaterThanOrEqualTo($payment->multipliedBy(3)))->toBeTrue();
    }
    $result = (new LoanCapacity)->evaluate('3020001', 6, BigRational::of('111/1000'), $cfads, BigRational::of(100000000));
    expect($result['eligible'])->toBeFalse()->and($result['offer'])->toBeNull()
        ->and($result['code'])->toBe('DSCR_BELOW_CUTOFF');
});
