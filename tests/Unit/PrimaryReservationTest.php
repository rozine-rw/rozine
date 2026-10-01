<?php

declare(strict_types=1);

use App\Domain\Primary\PrimaryReservation;
use App\Domain\Primary\PrimaryTerms;
use App\Domain\Primary\PrimaryViolation;
use App\Domain\Primary\ReservationWindow;
use App\Domain\Primary\UnitOrdinals;
use App\Domain\Primary\UnitRights;
use App\Domain\Underwriting\LoanSchedule;
use Brick\Math\BigRational;

/** @param array<string, mixed> $overrides */
function primaryDisclosedTerms(array $overrides = []): PrimaryTerms
{
    $inputs = array_replace([
        'ratePercent' => '10.0', 'termMonths' => 3, 'policyVersion' => 'synthetic-primary-1',
        'disclosureVersion' => 'synthetic-disclosure-1', 'rights' => primaryExampleRights(),
        'earningsFee' => ['tier' => 'standard', 'rate_bps' => 1000, 'basis' => 'return_only', 'policy_version' => 'synthetic-earnings-1'],
        'payoutFee' => '51',
    ], $overrides);

    return PrimaryTerms::disclosed(...$inputs);
}

function primaryExampleRights(): UnitRights
{
    return UnitRights::allocate('10000', ['3667', '3667', '3666'], UnitOrdinals::reserve('2', [], '1'));
}

function heldPrimaryReservation(): PrimaryReservation
{
    return PrimaryReservation::hold(
        primaryExampleRights(),
        primaryDisclosedTerms(),
        ReservationWindow::open(new DateTimeImmutable('2026-09-28T10:00:00Z'), new DateTimeImmutable('2026-09-29T10:00:00Z')),
    );
}

it('freezes the acknowledged terms and exact rights at confirmation without modifying the held snapshot', function (): void {
    $held = heldPrimaryReservation();
    $confirmed = $held->confirm(new DateTimeImmutable('2026-09-28T10:04:59Z'), primaryDisclosedTerms(), 'synthetic-disclosure-1', primaryDisclosedTerms()->disclosureSha256);
    expect($held->state)->toBe('held')->and($confirmed->state)->toBe('confirmed')
        ->and($confirmed->rights)->toBe($held->rights)->and($confirmed->terms)->toBe($held->terms)
        ->and($confirmed->window)->toBe($held->window)
        ->and($confirmed->terms->toArray())->toBe([
            'rate_pct' => '10.0', 'term_months' => 3, 'payout_fee' => ['currency' => 'RWF', 'amount' => '51'],
            'earnings_fee' => ['tier' => 'standard', 'rate_bps' => 1000, 'basis' => 'return_only', 'policy_version' => 'synthetic-earnings-1'],
            'policy_version' => 'synthetic-primary-1', 'disclosure_version' => 'synthetic-disclosure-1',
        ]);
    expect(fn () => $confirmed->confirm(new DateTimeImmutable('2026-09-28T10:04:59Z'), primaryDisclosedTerms(), 'synthetic-disclosure-1', primaryDisclosedTerms()->disclosureSha256))
        ->toThrow(PrimaryViolation::class, 'RESERVATION_NOT_HELD');
    expect(fn () => $confirmed->release(new DateTimeImmutable('2026-09-28T10:06:00Z')))
        ->toThrow(PrimaryViolation::class, 'RESERVATION_NOT_HELD');
});

it('rejects the old acknowledgement after any disclosed economic policy or rights change', function (array $change): void {
    $held = heldPrimaryReservation();
    expect(fn () => $held->confirm(new DateTimeImmutable('2026-09-28T10:01:00Z'), primaryDisclosedTerms($change), 'synthetic-disclosure-1', primaryDisclosedTerms()->disclosureSha256))
        ->toThrow(PrimaryViolation::class, 'DISCLOSURE_STALE');
    expect($held->state)->toBe('held');
})->with([
    [['ratePercent' => '10.1']],
    [['termMonths' => 4]],
    [['policyVersion' => 'synthetic-primary-2']],
    [['disclosureVersion' => 'synthetic-disclosure-2']],
    [['rights' => UnitRights::allocate('10000', ['3667', '3667', '3666'], UnitOrdinals::fromRanges('2', [['first' => '2', 'last' => '2']]))]],
    [['payoutFee' => '39']],
    [['earningsFee' => ['tier' => 'bronze', 'rate_bps' => 800, 'basis' => 'return_only', 'policy_version' => 'synthetic-earnings-1']]],
    [['earningsFee' => ['tier' => 'standard', 'rate_bps' => 1000, 'basis' => 'return_only', 'policy_version' => 'synthetic-earnings-2']]],
]);

it('requires acknowledgement of both the disclosure version and digest', function (string $version, string $digest): void {
    expect(fn () => heldPrimaryReservation()->confirm(new DateTimeImmutable('2026-09-28T10:01:00Z'), primaryDisclosedTerms(), $version, $digest))
        ->toThrow(PrimaryViolation::class, 'DISCLOSURE_STALE');
})->with([
    ['synthetic-disclosure-0', primaryDisclosedTerms()->disclosureSha256],
    ['synthetic-disclosure-1', str_repeat('b', 64)],
]);

it('allows unchanged disclosed terms regardless of array key ordering', function (): void {
    $current = primaryDisclosedTerms(['earningsFee' => ['policy_version' => 'synthetic-earnings-1', 'basis' => 'return_only', 'rate_bps' => 1000, 'tier' => 'standard']]);
    expect(heldPrimaryReservation()->confirm(new DateTimeImmutable('2026-09-28T10:01:00Z'), $current, 'synthetic-disclosure-1', primaryDisclosedTerms()->disclosureSha256)->state)->toBe('confirmed');
});

it('refuses first confirmation at expiry and returns a distinct expired release decision', function (): void {
    $held = heldPrimaryReservation();
    $deadline = new DateTimeImmutable('2026-09-28T10:05:00Z');
    expect(fn () => $held->confirm($deadline, primaryDisclosedTerms(), 'synthetic-disclosure-1', primaryDisclosedTerms()->disclosureSha256))
        ->toThrow(PrimaryViolation::class, 'RESERVATION_EXPIRED');
    $expired = $held->release($deadline);
    expect($expired->state)->toBe('expired')->and($expired->release($deadline))->toBe($expired)
        ->and($expired->rights)->toBe($held->rights);
    expect(fn () => $expired->confirm($deadline, primaryDisclosedTerms(), 'synthetic-disclosure-1', primaryDisclosedTerms()->disclosureSha256))
        ->toThrow(PrimaryViolation::class, 'RESERVATION_EXPIRED');
});

it('makes a released reservation terminal even before the clock expires', function (): void {
    $at = new DateTimeImmutable('2026-09-28T10:01:00Z');
    $released = heldPrimaryReservation()->release($at);
    expect($released->state)->toBe('released')->and($released->release($at))->toBe($released)
        ->and($released->release(new DateTimeImmutable('2026-09-28T11:00:00Z')))->toBe($released);
    expect(fn () => $released->confirm($at, primaryDisclosedTerms(), 'synthetic-disclosure-1', primaryDisclosedTerms()->disclosureSha256))
        ->toThrow(PrimaryViolation::class, 'RESERVATION_NOT_HELD');
    expect(fn () => heldPrimaryReservation()->release(new DateTimeImmutable('2026-09-28T09:59:59Z')))
        ->toThrow(PrimaryViolation::class, 'RESERVATION_NOT_STARTED');
});

it('refuses absent policy inputs rather than inventing a zero fee', function (array $change): void {
    expect(fn () => primaryDisclosedTerms($change))->toThrow(PrimaryViolation::class, 'POLICY_INPUT_REQUIRED');
})->with([
    [['earningsFee' => null]],
    [['policyVersion' => ' ']],
    [['earningsFee' => ['tier' => 'standard', 'rate_bps' => 1000, 'basis' => 'return_only', 'policy_version' => '']]],
]);

it('rejects invalid terms before creating a reservation', function (array $change): void {
    expect(fn () => primaryDisclosedTerms($change))->toThrow(PrimaryViolation::class, 'INVALID_PRIMARY_TERMS');
})->with([
    [['ratePercent' => '15.1']],
    [['ratePercent' => '9.9']],
    [['termMonths' => 2]],
    [['disclosureVersion' => '']],
    [['disclosureVersion' => "invalid-\xFF"]],
    [['payoutFee' => '-1']],
    [['payoutFee' => '01']],
    [['earningsFee' => ['tier' => 'other', 'rate_bps' => 1000, 'basis' => 'return_only', 'policy_version' => 'synthetic-1']]],
    [['earningsFee' => ['tier' => 'standard', 'rate_bps' => -1, 'basis' => 'return_only', 'policy_version' => 'synthetic-1']]],
    [['earningsFee' => ['tier' => 'standard', 'rate_bps' => 10001, 'basis' => 'return_only', 'policy_version' => 'synthetic-1']]],
    [['earningsFee' => ['tier' => 'standard', 'rate_bps' => 1000, 'basis' => 'principal', 'policy_version' => 'synthetic-1']]],
]);

it('binds quoted fees and tenor to the reserved rights', function (): void {
    $held = heldPrimaryReservation();
    foreach ([['termMonths' => 4], ['payoutFee' => '501']] as $change) {
        expect(fn () => PrimaryReservation::hold($held->rights, primaryDisclosedTerms($change), $held->window))
            ->toThrow(PrimaryViolation::class, 'INVALID_PRIMARY_TERMS');
    }
    expect(fn () => primaryDisclosedTerms(['payoutFee' => '0',
        'earningsFee' => ['tier' => 'standard', 'rate_bps' => 0, 'basis' => 'return_only', 'policy_version' => 'unapproved-zero']]))
        ->toThrow(PrimaryViolation::class, 'INVALID_PRIMARY_TERMS');
});

it('requires a fresh acknowledgement after requoting fees without extending or reallocating the reservation', function (): void {
    $at = new DateTimeImmutable('2026-09-28T10:04:00Z');
    $held = heldPrimaryReservation();
    $terms = primaryDisclosedTerms(['disclosureVersion' => 'synthetic-disclosure-2', 'payoutFee' => '39',
        'earningsFee' => ['tier' => 'bronze', 'rate_bps' => 800, 'basis' => 'return_only', 'policy_version' => 'synthetic-earnings-1']]);
    $requote = $held->requote($at, $terms);
    expect($requote->rights)->toBe($held->rights)->and($requote->window)->toBe($held->window)
        ->and($held->terms->payoutFee)->toBe('51')->and($requote->terms->payoutFee)->toBe('39');
    expect(fn () => $requote->confirm($at, $terms, 'synthetic-disclosure-1', primaryDisclosedTerms()->disclosureSha256))
        ->toThrow(PrimaryViolation::class, 'DISCLOSURE_STALE');
    $confirmed = $requote->confirm($at, $terms, 'synthetic-disclosure-2', $terms->disclosureSha256);
    expect($confirmed->terms)->toBe($terms)->and($confirmed->state)->toBe('confirmed');
    expect(fn () => $confirmed->requote($at, primaryDisclosedTerms()))->toThrow(PrimaryViolation::class, 'RESERVATION_NOT_HELD');
    expect(fn () => $requote->requote(new DateTimeImmutable('2026-09-28T10:05:00Z'), $terms))
        ->toThrow(PrimaryViolation::class, 'RESERVATION_EXPIRED');
});

it('never uses a fee requote to replace the purchased campaign rate or tenor', function (array $change): void {
    expect(fn () => heldPrimaryReservation()->requote(new DateTimeImmutable('2026-09-28T10:01:00Z'), primaryDisclosedTerms($change)))
        ->toThrow(PrimaryViolation::class, 'NOTE_INELIGIBLE');
})->with([[['ratePercent' => '10.1']], [['termMonths' => 4]]]);

it('rejects the previous acknowledgement after a fee change with either the same or a new version', function (string $version): void {
    $at = new DateTimeImmutable('2026-09-28T10:02:00Z');
    $bronze = primaryDisclosedTerms(['disclosureVersion' => $version, 'payoutFee' => '39',
        'earningsFee' => ['tier' => 'bronze', 'rate_bps' => 800, 'basis' => 'return_only', 'policy_version' => 'synthetic-earnings-1']]);
    expect(fn () => heldPrimaryReservation()->requote($at, $bronze)->confirm($at, $bronze, $version, primaryDisclosedTerms()->disclosureSha256))
        ->toThrow(PrimaryViolation::class, 'DISCLOSURE_STALE');
})->with(['synthetic-disclosure-1', 'synthetic-disclosure-2']);

it('rejects a fee quote that does not match the sum of rounded return-only instalment fees', function (): void {
    $held = heldPrimaryReservation();
    $diamond = primaryDisclosedTerms(['payoutFee' => '500',
        'earningsFee' => ['tier' => 'diamond', 'rate_bps' => 400, 'basis' => 'return_only', 'policy_version' => 'synthetic-earnings-1']]);
    expect(fn () => PrimaryReservation::hold($held->rights, $diamond, $held->window))
        ->toThrow(PrimaryViolation::class, 'INVALID_PRIMARY_TERMS');
});

it('rejects a tier whose rate differs from the approved Plus ladder', function (): void {
    expect(fn () => primaryDisclosedTerms(['earningsFee' => ['tier' => 'diamond', 'rate_bps' => 1000, 'basis' => 'return_only', 'policy_version' => 'synthetic-earnings-1']]))
        ->toThrow(PrimaryViolation::class, 'INVALID_PRIMARY_TERMS');
});

it('rejects unit rights from a different campaign rate before holding money', function (): void {
    $rights = UnitRights::allocate('10000', ['3834', '3834', '3832'], UnitOrdinals::reserve('2', [], '1'));
    expect(fn () => PrimaryReservation::hold($rights, primaryDisclosedTerms(['payoutFee' => '75', 'rights' => $rights]), heldPrimaryReservation()->window))
        ->toThrow(PrimaryViolation::class, 'INVALID_PRIMARY_TERMS');
});

it('reports the same expiration refusal before and after expiry processing', function (): void {
    $held = heldPrimaryReservation();
    $at = new DateTimeImmutable('2026-09-28T10:05:00Z');
    expect(fn () => $held->release($at)->confirm($at, primaryDisclosedTerms(), 'synthetic-disclosure-1', primaryDisclosedTerms()->disclosureSha256))
        ->toThrow(PrimaryViolation::class, 'RESERVATION_EXPIRED');
});

it('rejects missing or malformed fee fields with a domain refusal', function (mixed $fee): void {
    expect(fn () => primaryDisclosedTerms(['earningsFee' => $fee]))->toThrow(PrimaryViolation::class);
})->with([
    [[]],
    [['tier' => 'standard', 'rate_bps' => 1000, 'basis' => 'return_only']],
    [['tier' => 'standard', 'rate_bps' => 1000, 'basis' => 'return_only', 'policy_version' => 42]],
    [['tier' => 'standard', 'rate_bps' => '1000', 'basis' => 'return_only', 'policy_version' => 'synthetic-1']],
    [['tier' => null, 'rate_bps' => 1000, 'basis' => 'return_only', 'policy_version' => 'synthetic-1']],
    ['not-an-array'],
]);

it('accepts every approved tier with the sum of separately rounded return-only payout fees', function (string $tier, int $basisPoints, string $fee): void {
    $held = heldPrimaryReservation();
    $terms = primaryDisclosedTerms(['payoutFee' => $fee,
        'earningsFee' => ['tier' => $tier, 'rate_bps' => $basisPoints, 'basis' => 'return_only', 'policy_version' => 'synthetic-earnings-1']]);
    $reservation = PrimaryReservation::hold($held->rights, $terms, $held->window);
    expect(array_column($reservation->rights->instalments, 'return'))->toBe(['167', '167', '166'])
        ->and((string) $terms->scheduledPayoutFee($held->rights))->toBe($fee)
        ->and($reservation->terms->payoutFee)->toBe($fee);
})->with([
    ['standard', 1000, '51'], ['bronze', 800, '39'], ['silver', 650, '33'],
    ['gold', 550, '27'], ['platinum', 500, '24'], ['diamond', 400, '21'],
]);

it('rounds each half-franc return fee up without charging principal', function (): void {
    $rights = UnitRights::allocate('5000', ['1842', '1842', '1841'], UnitOrdinals::reserve('1', [], '1'));
    $terms = primaryDisclosedTerms(['ratePercent' => '10.5', 'payoutFee' => '54', 'rights' => $rights]);
    $reservation = PrimaryReservation::hold($rights, $terms, heldPrimaryReservation()->window);
    expect(array_column($rights->instalments, 'return'))->toBe(['175', '175', '175'])
        ->and((string) $rights->principal)->toBe('5000')
        ->and($reservation->terms->payoutFee)->toBe('54');
});

it('keeps fee arithmetic exact above native integer precision', function (): void {
    $rights = UnitRights::allocate('300000000000000000000', ['110000000000000000000', '110000000000000000000', '110000000000000000000'],
        UnitOrdinals::reserve('60000000000000000', [], '60000000000000000'));
    $terms = primaryDisclosedTerms(['payoutFee' => '3000000000000000000', 'rights' => $rights]);
    expect(PrimaryReservation::hold($rights, $terms, heldPrimaryReservation()->window)->terms->payoutFee)->toBe('3000000000000000000');
});

it('rejects aggregate rounding or zero fees when the scheduled payout fees differ', function (string $fee): void {
    $held = heldPrimaryReservation();
    expect(fn () => PrimaryReservation::hold($held->rights, primaryDisclosedTerms(['payoutFee' => $fee]), $held->window))
        ->toThrow(PrimaryViolation::class, 'INVALID_PRIMARY_TERMS');
})->with(['50', '0']);

it('allows an unchanged requote and still binds a changed policy to a fresh digest', function (): void {
    $held = heldPrimaryReservation();
    $at = new DateTimeImmutable('2026-09-28T10:01:00Z');
    expect($held->requote($at, primaryDisclosedTerms())->terms->toArray())->toBe($held->terms->toArray());
    $terms = primaryDisclosedTerms(['policyVersion' => 'synthetic-primary-2']);
    $requote = $held->requote($at, $terms);
    expect(fn () => $requote->confirm($at, $terms, 'synthetic-disclosure-1', primaryDisclosedTerms()->disclosureSha256))
        ->toThrow(PrimaryViolation::class, 'DISCLOSURE_STALE');
    expect($requote->confirm($at, $terms, 'synthetic-disclosure-1', $terms->disclosureSha256)->state)->toBe('confirmed');
});

it('never lets a historical disclosure identity authorize a different fee after multiple requotes', function (): void {
    $at = new DateTimeImmutable('2026-09-28T10:02:00Z');
    $original = primaryDisclosedTerms(['payoutFee' => '33',
        'earningsFee' => ['tier' => 'silver', 'rate_bps' => 650, 'basis' => 'return_only', 'policy_version' => 'synthetic-earnings-1']]);
    $held = PrimaryReservation::hold(heldPrimaryReservation()->rights, $original, heldPrimaryReservation()->window);
    $bronze = primaryDisclosedTerms(['disclosureVersion' => 'synthetic-disclosure-2', 'payoutFee' => '39',
        'earningsFee' => ['tier' => 'bronze', 'rate_bps' => 800, 'basis' => 'return_only', 'policy_version' => 'synthetic-earnings-1']]);
    $standard = primaryDisclosedTerms();
    $requote = $held->requote($at, $bronze)->requote($at, $standard);
    expect(fn () => $requote->confirm($at, $standard, $original->disclosureVersion, $original->disclosureSha256))
        ->toThrow(PrimaryViolation::class, 'DISCLOSURE_STALE');
});

it('reports the same expired requote refusal before and after expiry processing', function (): void {
    $held = heldPrimaryReservation();
    $at = new DateTimeImmutable('2026-09-28T10:05:00Z');
    expect(fn () => $held->requote($at, primaryDisclosedTerms()))->toThrow(PrimaryViolation::class, 'RESERVATION_EXPIRED');
    expect(fn () => $held->release($at)->requote($at, primaryDisclosedTerms()))->toThrow(PrimaryViolation::class, 'RESERVATION_EXPIRED');
});

it('refuses front-loaded campaign repayments even if their total and fee match the disclosed rate', function (): void {
    $rights = UnitRights::allocate('10000', ['4333', '3333', '3334'], UnitOrdinals::reserve('2', [], '1'));
    expect(fn () => PrimaryReservation::hold($rights, primaryDisclosedTerms(['payoutFee' => '50', 'rights' => $rights]), heldPrimaryReservation()->window))
        ->toThrow(PrimaryViolation::class, 'INVALID_PRIMARY_TERMS');
});

it('derives the canonical disclosure digest from a fixed exact-money vector', function (): void {
    expect(primaryDisclosedTerms()->disclosureSha256)->toBe('371dbfc636751b78a0ee078304387cfe1693d70a0f84b5fdc014e54679edb925');
    $reordered = primaryDisclosedTerms(['earningsFee' => ['policy_version' => 'synthetic-earnings-1', 'basis' => 'return_only', 'rate_bps' => 1000, 'tier' => 'standard']]);
    expect($reordered->disclosureSha256)->toBe(primaryDisclosedTerms()->disclosureSha256);
});

it('binds the disclosure to selected ordinal identities even when their payout amounts are identical', function (): void {
    $first = UnitRights::allocate('10000', ['2750', '2750', '2750', '2750'], UnitOrdinals::fromRanges('2', [['first' => '1', 'last' => '1']]));
    $second = UnitRights::allocate('10000', ['2750', '2750', '2750', '2750'], UnitOrdinals::fromRanges('2', [['first' => '2', 'last' => '2']]));
    $terms = primaryDisclosedTerms(['termMonths' => 4, 'payoutFee' => '52', 'rights' => $first]);
    $other = primaryDisclosedTerms(['termMonths' => 4, 'payoutFee' => '52', 'rights' => $second]);
    expect($first->toArray())->toBe($second->toArray())
        ->and($terms->toArray())->toBe($other->toArray())
        ->and($terms->disclosureSha256)->not->toBe($other->disclosureSha256);
    expect(fn () => PrimaryReservation::hold($second, $terms, heldPrimaryReservation()->window))
        ->toThrow(PrimaryViolation::class, 'INVALID_PRIMARY_TERMS');
    expect(PrimaryReservation::hold($second, $other, heldPrimaryReservation()->window)->state)->toBe('held');
});

it('refuses version strings that cannot be canonicalized safely', function (string $field, string $value): void {
    $change = $field === 'fee' ? ['earningsFee' => ['tier' => 'standard', 'rate_bps' => 1000, 'basis' => 'return_only', 'policy_version' => $value]] : [$field => $value];
    expect(fn () => primaryDisclosedTerms($change))->toThrow(PrimaryViolation::class, 'INVALID_PRIMARY_TERMS');
})->with(['policyVersion', 'disclosureVersion', 'fee'])->with(["invalid-\xFF", "line\u{2028}separator", "paragraph\u{2029}separator"]);

it('accepts retained underwriting schedules for every allowed tenor without changing their rights', function (int $tenor): void {
    $schedule = LoanSchedule::build('10700000', $tenor, BigRational::of('121/1000'));
    $rights = UnitRights::allocate('10700000', array_map(strval(...), $schedule->instalments),
        UnitOrdinals::fromRanges('2140', [['first' => '1', 'last' => '3'], ['first' => '2140', 'last' => '2140']]));
    $fee = array_sum(array_map(fn (array $payout): int => intdiv((int) $payout['return'] * 1000 + 5000, 10000), $rights->instalments));
    $terms = primaryDisclosedTerms(['ratePercent' => '12.1', 'termMonths' => $tenor, 'payoutFee' => (string) $fee, 'rights' => $rights]);
    expect(PrimaryReservation::hold($rights, $terms, heldPrimaryReservation()->window)->rights)->toBe($rights);
})->with([3, 4, 5, 6]);
