<?php

declare(strict_types=1);

use App\Domain\Primary\PrimaryReservation;
use App\Domain\Primary\PrimaryTerms;
use App\Domain\Primary\PrimaryViolation;
use App\Domain\Primary\ReservationWindow;
use App\Domain\Primary\UnitOrdinals;
use App\Domain\Primary\UnitRights;

/** @param array<string, mixed> $overrides */
function primaryDisclosedTerms(array $overrides = []): PrimaryTerms
{
    $inputs = array_replace([
        'ratePercent' => '10.0', 'termMonths' => 3, 'policyVersion' => 'synthetic-primary-1',
        'disclosureVersion' => 'synthetic-disclosure-1', 'disclosureSha256' => str_repeat('a', 64),
        'earningsFee' => ['tier' => 'standard', 'rate_bps' => 1000, 'basis' => 'return_only', 'policy_version' => 'synthetic-earnings-1'],
        'payoutFee' => '50',
    ], $overrides);

    return PrimaryTerms::disclosed(...$inputs);
}

function heldPrimaryReservation(): PrimaryReservation
{
    return PrimaryReservation::hold(
        UnitRights::allocate('10000', ['3667', '3667', '3666'], UnitOrdinals::reserve('2', [], '1')),
        primaryDisclosedTerms(),
        ReservationWindow::open(new DateTimeImmutable('2026-09-28T10:00:00Z'), new DateTimeImmutable('2026-09-29T10:00:00Z')),
    );
}

it('freezes the acknowledged terms and exact rights at confirmation without modifying the held snapshot', function (): void {
    $held = heldPrimaryReservation();
    $confirmed = $held->confirm(new DateTimeImmutable('2026-09-28T10:04:59Z'), primaryDisclosedTerms(), 'synthetic-disclosure-1', str_repeat('a', 64));
    expect($held->state)->toBe('held')->and($confirmed->state)->toBe('confirmed')
        ->and($confirmed->rights)->toBe($held->rights)->and($confirmed->terms)->toBe($held->terms)
        ->and($confirmed->window)->toBe($held->window)
        ->and($confirmed->terms->toArray())->toBe([
            'rate_pct' => '10.0', 'term_months' => 3, 'payout_fee' => ['currency' => 'RWF', 'amount' => '50'],
            'earnings_fee' => ['tier' => 'standard', 'rate_bps' => 1000, 'basis' => 'return_only', 'policy_version' => 'synthetic-earnings-1'],
            'policy_version' => 'synthetic-primary-1', 'disclosure_version' => 'synthetic-disclosure-1',
        ]);
    expect(fn () => $confirmed->confirm(new DateTimeImmutable('2026-09-28T10:04:59Z'), primaryDisclosedTerms(), 'synthetic-disclosure-1', str_repeat('a', 64)))
        ->toThrow(PrimaryViolation::class, 'RESERVATION_NOT_HELD');
    expect(fn () => $confirmed->release(new DateTimeImmutable('2026-09-28T10:06:00Z')))
        ->toThrow(PrimaryViolation::class, 'RESERVATION_NOT_HELD');
});

it('rejects any changed disclosed economic or policy input even under an unchanged disclosure identity', function (array $change): void {
    $held = heldPrimaryReservation();
    expect(fn () => $held->confirm(new DateTimeImmutable('2026-09-28T10:01:00Z'), primaryDisclosedTerms($change), 'synthetic-disclosure-1', str_repeat('a', 64)))
        ->toThrow(PrimaryViolation::class, 'DISCLOSURE_STALE');
    expect($held->state)->toBe('held');
})->with([
    [['ratePercent' => '10.1']],
    [['termMonths' => 4]],
    [['policyVersion' => 'synthetic-primary-2']],
    [['disclosureVersion' => 'synthetic-disclosure-2']],
    [['disclosureSha256' => str_repeat('b', 64)]],
    [['payoutFee' => '40']],
    [['earningsFee' => ['tier' => 'bronze', 'rate_bps' => 800, 'basis' => 'return_only', 'policy_version' => 'synthetic-earnings-1']]],
    [['earningsFee' => ['tier' => 'standard', 'rate_bps' => 1000, 'basis' => 'return_only', 'policy_version' => 'synthetic-earnings-2']]],
]);

it('requires acknowledgement of both the disclosure version and digest', function (string $version, string $digest): void {
    expect(fn () => heldPrimaryReservation()->confirm(new DateTimeImmutable('2026-09-28T10:01:00Z'), primaryDisclosedTerms(), $version, $digest))
        ->toThrow(PrimaryViolation::class, 'DISCLOSURE_STALE');
})->with([
    ['synthetic-disclosure-0', str_repeat('a', 64)],
    ['synthetic-disclosure-1', str_repeat('b', 64)],
]);

it('allows unchanged disclosed terms regardless of array key ordering', function (): void {
    $current = primaryDisclosedTerms(['earningsFee' => ['policy_version' => 'synthetic-earnings-1', 'basis' => 'return_only', 'rate_bps' => 1000, 'tier' => 'standard']]);
    expect(heldPrimaryReservation()->confirm(new DateTimeImmutable('2026-09-28T10:01:00Z'), $current, 'synthetic-disclosure-1', str_repeat('a', 64))->state)->toBe('confirmed');
});

it('refuses first confirmation at expiry and returns a distinct expired release decision', function (): void {
    $held = heldPrimaryReservation();
    $deadline = new DateTimeImmutable('2026-09-28T10:05:00Z');
    expect(fn () => $held->confirm($deadline, primaryDisclosedTerms(), 'synthetic-disclosure-1', str_repeat('a', 64)))
        ->toThrow(PrimaryViolation::class, 'RESERVATION_EXPIRED');
    $expired = $held->release($deadline);
    expect($expired->state)->toBe('expired')->and($expired->release($deadline))->toBe($expired)
        ->and($expired->rights)->toBe($held->rights);
    expect(fn () => $expired->confirm($deadline, primaryDisclosedTerms(), 'synthetic-disclosure-1', str_repeat('a', 64)))
        ->toThrow(PrimaryViolation::class, 'RESERVATION_NOT_HELD');
});

it('makes a released reservation terminal even before the clock expires', function (): void {
    $at = new DateTimeImmutable('2026-09-28T10:01:00Z');
    $released = heldPrimaryReservation()->release($at);
    expect($released->state)->toBe('released')->and($released->release($at))->toBe($released)
        ->and($released->release(new DateTimeImmutable('2026-09-28T11:00:00Z')))->toBe($released);
    expect(fn () => $released->confirm($at, primaryDisclosedTerms(), 'synthetic-disclosure-1', str_repeat('a', 64)))
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
    [['disclosureSha256' => 'not-a-digest']],
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
    expect(PrimaryReservation::hold($held->rights, primaryDisclosedTerms(['payoutFee' => '0',
        'earningsFee' => ['tier' => 'standard', 'rate_bps' => 0, 'basis' => 'return_only', 'policy_version' => 'explicit-synthetic-zero']]), $held->window)->terms->payoutFee)->toBe('0');
});

it('requires a fresh acknowledgement after requoting fees without extending or reallocating the reservation', function (): void {
    $at = new DateTimeImmutable('2026-09-28T10:04:00Z');
    $held = heldPrimaryReservation();
    $terms = primaryDisclosedTerms(['disclosureVersion' => 'synthetic-disclosure-2', 'disclosureSha256' => str_repeat('b', 64), 'payoutFee' => '40',
        'earningsFee' => ['tier' => 'bronze', 'rate_bps' => 800, 'basis' => 'return_only', 'policy_version' => 'synthetic-earnings-1']]);
    $requote = $held->requote($at, $terms);
    expect($requote->rights)->toBe($held->rights)->and($requote->window)->toBe($held->window)
        ->and($held->terms->payoutFee)->toBe('50')->and($requote->terms->payoutFee)->toBe('40');
    expect(fn () => $requote->confirm($at, $terms, 'synthetic-disclosure-1', str_repeat('a', 64)))
        ->toThrow(PrimaryViolation::class, 'DISCLOSURE_STALE');
    $confirmed = $requote->confirm($at, $terms, 'synthetic-disclosure-2', str_repeat('b', 64));
    expect($confirmed->terms)->toBe($terms)->and($confirmed->state)->toBe('confirmed');
    expect(fn () => $confirmed->requote($at, primaryDisclosedTerms()))->toThrow(PrimaryViolation::class, 'RESERVATION_NOT_HELD');
    expect(fn () => $requote->requote(new DateTimeImmutable('2026-09-28T10:05:00Z'), $terms))
        ->toThrow(PrimaryViolation::class, 'RESERVATION_EXPIRED');
});

it('never uses a fee requote to replace the purchased campaign rate or tenor', function (array $change): void {
    expect(fn () => heldPrimaryReservation()->requote(new DateTimeImmutable('2026-09-28T10:01:00Z'), primaryDisclosedTerms($change)))
        ->toThrow(PrimaryViolation::class, 'DISCLOSURE_STALE');
})->with([[['ratePercent' => '10.1']], [['termMonths' => 4]]]);
