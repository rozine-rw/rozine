<?php

declare(strict_types=1);

use App\Domain\Primary\PrimaryViolation;
use App\Domain\Primary\ReservationWindow;

it('limits checkout to five minutes with an exclusive deadline', function (): void {
    $created = new DateTimeImmutable('2026-09-28T10:00:00.000000Z');
    $window = ReservationWindow::open($created, new DateTimeImmutable('2026-10-01T10:00:00Z'));
    expect($window->expiresAt->format('c'))->toBe('2026-09-28T10:05:00+00:00')
        ->and($window->isExpired($created))->toBeFalse();
    $window->requireOpen(new DateTimeImmutable('2026-09-28T10:04:59.999999Z'));
    expect(fn () => $window->requireOpen(new DateTimeImmutable('2026-09-28T10:05:00Z')))
        ->toThrow(PrimaryViolation::class, 'RESERVATION_EXPIRED');
    expect($window->isExpired(new DateTimeImmutable('2026-09-29T10:00:00Z')))->toBeTrue();
});

it('never grants a checkout time past campaign expiry', function (): void {
    $window = ReservationWindow::open(new DateTimeImmutable('2026-09-28T10:00:00Z'), new DateTimeImmutable('2026-09-28T10:02:00Z'));
    expect($window->expiresAt->format('c'))->toBe('2026-09-28T10:02:00+00:00');
    expect(fn () => $window->requireOpen($window->expiresAt))->toThrow(PrimaryViolation::class, 'RESERVATION_EXPIRED');
});

it('compares absolute instants across timezone and daylight saving changes', function (): void {
    $window = ReservationWindow::open(new DateTimeImmutable('2026-03-08 01:58:00', new DateTimeZone('America/New_York')),
        new DateTimeImmutable('2026-03-09T00:00:00Z'));
    expect($window->startsAt->format('c'))->toBe('2026-03-08T06:58:00+00:00')
        ->and($window->expiresAt->format('c'))->toBe('2026-03-08T07:03:00+00:00');
    $window->requireOpen(new DateTimeImmutable('2026-03-08T09:02:59+02:00'));
});

it('refuses reservations at or after the campaign deadline', function (string $created): void {
    expect(fn () => ReservationWindow::open(new DateTimeImmutable($created), new DateTimeImmutable('2026-09-28T10:00:00Z')))
        ->toThrow(PrimaryViolation::class, 'CAMPAIGN_CLOSED');
})->with(['2026-09-28T10:00:00Z', '2026-09-28T10:00:01Z']);

it('rejects a confirmation instant before reservation creation', function (): void {
    $window = ReservationWindow::open(new DateTimeImmutable('2026-09-28T10:00:00Z'), new DateTimeImmutable('2026-10-01T10:00:00Z'));
    expect(fn () => $window->requireOpen(new DateTimeImmutable('2026-09-28T09:59:59Z')))
        ->toThrow(PrimaryViolation::class, 'RESERVATION_NOT_STARTED');
});
