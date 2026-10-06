<?php

declare(strict_types=1);

use App\Domain\Underwriting\CashFlowEvidence;
use App\Domain\Underwriting\UnderwritingObservationWindow;
use App\Domain\Underwriting\UnderwritingViolation;
use Tests\Support\UnderwritingEvidenceFixture as Fixture;

it('selects the latest complete baseline or repeat window without consuming partial months', function (bool $repeat, int $count, string $first): void {
    $months = [...Fixture::months(42), [...Fixture::months(1)[0], 'month' => '2026-09'], [...Fixture::months(1)[0], 'month' => '2026-10']];
    $before = $months;
    $selected = (new UnderwritingObservationWindow)->select(array_reverse($months), new DateTimeImmutable('2026-09-25T00:00:00Z'), $repeat);
    expect($selected['months'])->toHaveCount($count)->and($selected['months'][0]['month'])->toBe($first)
        ->and($selected['months'][$count - 1]['month'])->toBe('2026-08')
        ->and($selected['calendar'])->toBe(['last_complete_month' => '2026-08', 'first_repayment_month' => '2026-09'])
        ->and($selected['valid_through'])->toBe('2026-10-15')->and($selected['fresh'])->toBeTrue()
        ->and($months)->toBe($before);
})->with([[false, 36, '2023-09'], [true, 12, '2025-09']]);

it('uses Kigali calendar days for inclusive 45-day freshness across leap months and year boundaries', function (string $month, string $instant, string $validThrough, bool $fresh): void {
    $selected = (new UnderwritingObservationWindow)->select([[...Fixture::months(1)[0], 'month' => $month]], new DateTimeImmutable($instant), false);
    expect($selected['valid_through'])->toBe($validThrough)->and($selected['fresh'])->toBe($fresh);
})->with([
    ['2026-08', '2026-10-14T21:59:59Z', '2026-10-15', true],
    ['2026-08', '2026-10-15T21:59:59Z', '2026-10-15', true],
    ['2026-08', '2026-10-15T22:00:00Z', '2026-10-15', false],
    ['2024-02', '2024-04-14T21:59:59Z', '2024-04-14', true],
    ['2024-02', '2024-04-14T22:00:00Z', '2024-04-14', false],
    ['2025-12', '2026-02-14T21:59:59Z', '2026-02-14', true],
    ['2025-12', '2026-02-14T22:00:00Z', '2026-02-14', false],
]);

it('treats a month as complete only once Kigali has crossed its boundary', function (): void {
    $months = [...Fixture::months(36), [...Fixture::months(1)[0], 'month' => '2026-09']];
    $window = new UnderwritingObservationWindow;
    $before = $window->select($months, new DateTimeImmutable('2026-09-30T21:59:59Z'), false);
    $after = $window->select($months, new DateTimeImmutable('2026-09-30T22:00:00Z'), false);
    expect($before['calendar'])->toBe(['last_complete_month' => '2026-08', 'first_repayment_month' => '2026-09'])
        ->and($after['calendar'])->toBe(['last_complete_month' => '2026-09', 'first_repayment_month' => '2026-10'])
        ->and($after['months'][0]['month'])->toBe('2023-10')->and($after['months'])->toHaveCount(36);
});

it('leaves missing duplicate and unverified selected months for the evidence gate to refuse', function (string $fault): void {
    $months = Fixture::months();
    if ($fault === 'gap') {
        unset($months[20]);
        $months = array_values($months);
    } elseif ($fault === 'duplicate') {
        $months[] = $months[35];
    } else {
        $months = array_map(fn (array $month): array => $month['month'] === '2026-08' ? [...$month, 'verified' => false] : $month, $months);
    }
    $selected = (new UnderwritingObservationWindow)->select($months, new DateTimeImmutable('2026-10-01T00:00:00Z'), false);
    expect($selected['fresh'])->toBeTrue()->and($selected['calendar']['last_complete_month'])->toBe('2026-08');
    expect(fn () => (new CashFlowEvidence)->analyze($selected['months'], '2026-08', '2026-10', 6, '0', []))
        ->toThrow(UnderwritingViolation::class, 'UNDERWRITING_EVIDENCE_REQUIRED');
})->with(['gap', 'duplicate', 'unverified']);

it('does not invent complete evidence when only current or future months exist', function (): void {
    $months = [[...Fixture::months(1)[0], 'month' => '2026-09'], [...Fixture::months(1)[0], 'month' => '2026-10']];
    foreach ([[], $months] as $source) {
        $selected = (new UnderwritingObservationWindow)->select($source, new DateTimeImmutable('2026-09-25T00:00:00Z'), false);
        expect($selected['months'])->toBe([])->and($selected['valid_through'])->toBeNull()->and($selected['fresh'])->toBeFalse()
            ->and($selected['calendar'])->toBe(['last_complete_month' => '2026-08', 'first_repayment_month' => '2026-09']);
    }
});

it('refuses malformed observation months instead of selecting around them', function (string $month): void {
    expect(fn () => (new UnderwritingObservationWindow)->select([[...Fixture::months(1)[0], 'month' => $month]], new DateTimeImmutable('2026-09-25T00:00:00Z'), false))
        ->toThrow(UnderwritingViolation::class, 'INVALID_OBSERVATION_MONTH');
})->with(['bad', '2026-00', '2026-13', '0999-12']);
