<?php

declare(strict_types=1);

use App\Domain\Underwriting\LoanCapacity;
use App\Domain\Underwriting\LoanSchedule;
use App\Domain\Underwriting\UnderwritingViolation;
use Brick\Math\BigRational;

it('applies the v3 cutoff to historical sizing vectors without rewriting their source', function (array $example): void {
    $input = $example['inputs'];
    $expected = $example['expected'];
    $result = (new LoanCapacity)->evaluate((string) $input['requested_rwf'], $input['tenor_months'], BigRational::ofFraction($input['rate_tenths_percent'], 1000), BigRational::of($input['cfads_rwf']), BigRational::of($input['remaining_room_rwf']));
    $originalTotal = $input['requested_rwf'] + intdiv($input['requested_rwf'] * $input['rate_tenths_percent'] + 500, 1000);
    if ($originalTotal * 3 > $input['cfads_rwf'] * $input['tenor_months'] * 2) {
        expect($result['eligible'])->toBeFalse()->and($result['offer'])->toBeNull()
            ->and($result['code'])->toBe('DSCR_BELOW_CUTOFF')->and($result['raw_capacity'])->toBeNull();

        return;
    }
    if ($expected['principal_rwf'] === null) {
        expect($result['eligible'])->toBeFalse()->and($result['offer'])->toBeNull()
            ->and($result['code'])->toBe($expected['status'] === 'REJECT_ORIGINAL' ? 'DSCR_BELOW_CUTOFF' : 'CAPACITY_BELOW_MINIMUM');

        return;
    }
    expect($result['eligible'])->toBeTrue()
        ->and($result['guarded_principal']['amount'])->toBe((string) $expected['principal_rwf'])
        ->and($result['rounding_guard']['amount'])->toBe((string) $expected['rounding_guard_rwf'])
        ->and($result['offer']['principal']['amount'])->toBe((string) (intdiv($expected['principal_rwf'], 5000) * 5000));
    expect(BigRational::ofFraction($result['final_dscr']['numerator'], $result['final_dscr']['denominator'])->isGreaterThanOrEqualTo('3/2'))->toBeTrue();
})->with(function (): array {
    $contents = file_get_contents(__DIR__.'/../../docs/phase-0/engineering-contract-fixtures-2026-09-20.json');
    if ($contents === false) {
        throw new UnexpectedValueException('Missing approved engineering fixtures.');
    }
    $pack = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

    return array_map(fn (array $example): array => [$example], array_values(array_filter($pack['numeric_cases'], fn (array $example): bool => $example['kind'] === 'sizing')));
});

it('requires an affordable new request instead of the historical EC-010 counteroffer', function (): void {
    $result = (new LoanCapacity)->evaluate('12000000', 6, BigRational::of('121/1000'), BigRational::of(3000000), BigRational::of(100000000));
    expect($result['code'])->toBe('DSCR_BELOW_CUTOFF')->and($result['offer'])->toBeNull();
    $result = (new LoanCapacity)->evaluate('10700000', 6, BigRational::of('121/1000'), BigRational::of(3000000), BigRational::of(100000000));
    expect($result['offer']['principal']['amount'])->toBe('10700000')->and($result['offer']['units'])->toBe('2140')
        ->and($result['offer']['contractual_return']['amount'])->toBe('1294700')->and($result['offer']['total']['amount'])->toBe('11994700')
        ->and($result['reason_codes'])->toBe([]);
});

it('does not resize a rejected original request even for a smaller acceptance', function (): void {
    $result = (new LoanCapacity)->evaluate('10000000', 6, BigRational::of('121/1000'), BigRational::of(2000000), BigRational::of(100000000), acceptedPrincipal: '3000000');
    expect($result['code'])->toBe('DSCR_BELOW_CUTOFF')->and($result['raw_capacity'])->toBeNull();
});

it('compares the original cutoff exactly on each side of equality', function (string $cfads, bool $passes): void {
    $result = (new LoanCapacity)->evaluate('3000000', 3, BigRational::of('1/10'), BigRational::of($cfads), BigRational::of(100000000));
    expect($result['code'] !== 'DSCR_BELOW_CUTOFF')->toBe($passes);
})->with([['1649999', false], ['1650000', true], ['1650001', true], ['-1', false]]);

it('preserves fractional and negative exposure without rounding into eligibility', function (string $room): void {
    $result = (new LoanCapacity)->evaluate('10700000', 6, BigRational::of('121/1000'), BigRational::of(3000000), BigRational::of($room));
    expect($result['code'])->toBe('CAPACITY_BELOW_MINIMUM')->and($result['offer'])->toBeNull()
        ->and($result['remaining_room']['numerator'])->toBe((string) BigRational::of($room)->getNumerator());
})->with(['2999999.65', '-500000', '0']);

it('floors fractional exposure and caps high capacity at the principal maximum', function (): void {
    $limited = (new LoanCapacity)->evaluate('10700000', 6, BigRational::of('121/1000'), BigRational::of(3000000), BigRational::of('9000000.75'));
    expect($limited['rounded_candidate']['amount'])->toBe('9000000')->and($limited['reason_codes'])->toContain('EXPOSURE_CAPPED');
    $capped = (new LoanCapacity)->evaluate('150000000', 6, BigRational::of('1/10'), BigRational::of(100000000), BigRational::of(1000000000));
    expect($capped['offer']['principal']['amount'])->toBe('100000000')->and($capped['reason_codes'])->toContain('PRINCIPAL_MAX_CAPPED');
});

it('rebuilds a lower accepted schedule without bypassing caps', function (): void {
    $result = (new LoanCapacity)->evaluate('10700000', 6, BigRational::of('121/1000'), BigRational::of(3000000), BigRational::of(100000000), acceptedPrincipal: '8000000');
    expect($result['offer']['principal']['amount'])->toBe('8000000')->and($result['offer']['total']['amount'])->toBe('8968000')
        ->and($result['offer']['instalments'][5]['amount'])->toBe('1494665');
    foreach (['2995000', '8000001', '10705000'] as $accepted) {
        expect(fn () => (new LoanCapacity)->evaluate('10700000', 6, BigRational::of('121/1000'), BigRational::of(3000000), BigRational::of(100000000), acceptedPrincipal: $accepted))->toThrow(UnderwritingViolation::class, 'INVALID_ACCEPTED_PRINCIPAL');
    }
});

it('plans a smaller repeat request at the single threshold without counteroffering', function (): void {
    $monthly = array_map(BigRational::of(...), [2000000, 2100000, 2000000, 2200000, 2000000, 2000000]);
    $calculator = new LoanCapacity;
    $result = $calculator->evaluate('10700000', 6, BigRational::of('121/1000'), BigRational::of(3000000), BigRational::of(100000000), $monthly);
    expect($result['eligible'])->toBeFalse()->and($result['offer'])->toBeNull()->and($result['code'])->toBe('DSCR_BELOW_CUTOFF');
    $plan = $calculator->plan(6, BigRational::of('121/1000'), BigRational::of(3000000), BigRational::of(100000000), $monthly);
    expect($plan)->not->toBeNull();
    $schedule = LoanSchedule::build($plan['principal']['amount'], 6, BigRational::of('121/1000'));
    foreach ($monthly as $index => $cfads) {
        expect($cfads->multipliedBy(2)->isGreaterThanOrEqualTo($schedule->instalments[$index]->multipliedBy(3)))->toBeTrue();
    }
    $next = LoanSchedule::build((string) ((int) $plan['principal']['amount'] + 5000), 6, BigRational::of('121/1000'));
    expect($monthly[0]->dividedBy($next->instalments[0])->isLessThan('3/2'))->toBeTrue();
});

it('denies repeat offers with a weak or missing month and preserves a strong-month offer', function (): void {
    $positive = array_fill(0, 6, BigRational::of(3000000));
    expect((new LoanCapacity)->evaluate('10700000', 6, BigRational::of('121/1000'), BigRational::of(3000000), BigRational::of(100000000), $positive)['offer']['principal']['amount'])->toBe('10700000');
    $positive[5] = BigRational::of(-1);
    expect((new LoanCapacity)->evaluate('10700000', 6, BigRational::of('121/1000'), BigRational::of(3000000), BigRational::of(100000000), $positive)['code'])->toBe('DSCR_BELOW_CUTOFF');
    expect(fn () => (new LoanCapacity)->evaluate('10700000', 6, BigRational::of('121/1000'), BigRational::of(3000000), BigRational::of(100000000), [BigRational::of(3000000)]))->toThrow(UnderwritingViolation::class, 'UNDERWRITING_EVIDENCE_REQUIRED');
});
